<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Catalog\Models\ResponsibleParty;
use App\Domain\Catalog\ResponsibleResolver;
use App\Domain\Organizations\Models\Contractor;
use App\Domain\Organizations\Models\Executor;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Dto\ClosingPhoto;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Dto\RedirectData;
use App\Domain\Requests\Enums\ActorRole;
use App\Domain\Requests\Enums\AttachmentKind;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Events\ParticipantJoined;
use App\Domain\Requests\Events\RequestCreated;
use App\Domain\Requests\Events\RequestDueSoon;
use App\Domain\Requests\Events\RequestOverdue;
use App\Domain\Requests\Events\RequestStatusChanged;
use App\Domain\Requests\Exceptions\EmergencyCategory;
use App\Domain\Requests\Exceptions\InvalidTransition;
use App\Domain\Requests\Exceptions\NotAllowed;
use App\Domain\Requests\Models\RequestEvent;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;
use App\Support\Images\ImageSanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class RequestService
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ResponsibleResolver $resolver,
        private readonly DeadlineCalculator $deadlines,
        private readonly ImageSanitizer $images,
    ) {}

    public function create(CreateRequestData $data): ServiceRequest
    {
        $category = $this->catalog->leafOrFail($data->categoryId);
        if ($category->is_emergency) {
            throw new EmergencyCategory('Это авария: звоните в аварийную службу, заявка не создаётся', ['category_id' => $category->id]);
        }

        $house = House::query()->with(['organization', 'region'])->findOrFail($data->houseId);
        $responsible = $this->resolver->resolve($category, $house);
        $now = CarbonImmutable::now();
        $timezone = $house->region->timezone;

        $request = DB::transaction(function () use ($data, $category, $house, $responsible, $now, $timezone): ServiceRequest {
            $request = ServiceRequest::query()->create([
                'organization_id' => $house->organization_id,
                'house_id' => $house->id,
                'resident_user_id' => $data->residentUserId,
                'category_id' => $category->id,
                'description' => $data->description,
                'entrance' => $data->entrance,
                'flat' => $data->flat,
                'responsible_kind' => $responsible->kind,
                'responsible_party_id' => $responsible->partyId,
                'responsible_name' => $responsible->name,
                'responsible_phone' => $responsible->phone,
                'is_sure' => $responsible->isSure && ! $data->residentUnsure,
                'deadline_fix_at' => $this->deadlines->fixDeadline($category, $now, $timezone),
                'deadline_reply_at' => $this->deadlines->replyDeadline($category, $now, $timezone),
                'basis' => (string) $category->basis,
                'status' => RequestStatus::New,
                'origin' => $data->origin,
                'source_chat_id' => $data->sourceChatId,
                'repeat_of_id' => $data->repeatOfId,
            ]);

            $this->log($request, EventType::Created, new Actor(ActorRole::Resident, $data->residentUserId), null, [
                'responsible' => $responsible->name,
                'hint' => $responsible->hint,
                'photos' => count($data->photos),
            ]);

            foreach ($data->photos as $photo) {
                $request->attachments()->create([
                    'kind' => AttachmentKind::Resident,
                    'disk' => (string) config('attachments.disk'),
                    'path' => '',
                    'mime' => 'image/jpeg',
                    'size_bytes' => 1,
                    'max_token' => $photo->maxToken,
                    'uploaded_by' => $data->residentUserId,
                ]);
            }

            $this->publish($request, new RequestCreated($request));

            return $request;
        });

        return $request->refresh();
    }

    /** @param array<string,mixed> $payload */
    public function transition(ServiceRequest $request, RequestStatus $to, Actor $by, ?string $comment = null, array $payload = []): ServiceRequest
    {
        return DB::transaction(fn (): ServiceRequest => $this->move($request, $to, $by, $comment, $payload)[0]);
    }

    public function assign(ServiceRequest $request, Executor $executor, Actor $by, ?string $comment = null): ServiceRequest
    {
        if ($executor->organization_id !== $request->organization_id) {
            throw new NotAllowed('Исполнитель из другой организации');
        }

        return DB::transaction(function () use ($request, $executor, $by, $comment): ServiceRequest {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $locked->status->isOpen()) {
                throw InvalidTransition::between($locked->status, RequestStatus::Assigned);
            }

            $now = CarbonImmutable::now();
            $locked->executor_id = $executor->id;
            $locked->assigned_at = $now;
            $this->markReaction($locked, $by, $now);
            $locked->save();
            $this->log($locked, EventType::Assigned, $by, $comment, ['executor_id' => $executor->id, 'executor' => $executor->name]);

            return $locked->status->canTransitionTo(RequestStatus::Assigned)
                ? $this->transition($locked, RequestStatus::Assigned, $by, null, ['executor' => $executor->name])
                : $locked;
        });
    }

    public function start(ServiceRequest $request, Actor $by, ?string $comment = null): ServiceRequest
    {
        return $this->transition($request, RequestStatus::InProgress, $by, $comment);
    }

    /** @param list<ClosingPhoto> $photos */
    public function close(ServiceRequest $request, Actor $by, ?string $comment = null, array $photos = []): ServiceRequest
    {
        $disk = (string) config('attachments.disk');
        $written = [];

        try {
            return DB::transaction(function () use ($request, $by, $comment, $photos, $disk, &$written): ServiceRequest {
                [$locked, $event] = $this->move($request, RequestStatus::Done, $by, $comment);
                foreach ($photos as $photo) {
                    $written[] = $this->storeClosingPhoto($locked, $event, $photo, $disk, $by);
                }

                return $locked;
            });
        } catch (Throwable $e) {
            if ($written !== []) {
                Storage::disk($disk)->delete($written);
            }

            throw $e;
        }
    }

    public function confirm(ServiceRequest $request, Actor $by, ?string $comment = null): ServiceRequest
    {
        return $this->transition($request, RequestStatus::Confirmed, $by, $comment);
    }

    public function returnToWork(ServiceRequest $request, Actor $by, ?string $comment = null): ServiceRequest
    {
        return $this->transition($request, RequestStatus::Returned, $by, $comment);
    }

    public function redirect(ServiceRequest $request, Actor $by, RedirectData $data): ServiceRequest
    {
        return DB::transaction(function () use ($request, $by, $data): ServiceRequest {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $locked->status->canTransitionTo(RequestStatus::Redirected)) {
                throw InvalidTransition::between($locked->status, RequestStatus::Redirected);
            }

            [$party, $name, $phone] = $this->redirectTarget($locked, $data);
            $locked->redirected_party_id = $party?->id;
            $locked->redirect_name = $name;
            $locked->redirect_phone = $phone;
            $locked->redirect_note = $data->note;
            $locked->save();

            return $this->move($locked, RequestStatus::Redirected, $by, $data->note, ['to' => $name, 'phone' => $phone])[0];
        });
    }

    public function comment(ServiceRequest $request, Actor $by, string $text): RequestEvent
    {
        return DB::transaction(function () use ($request, $by, $text): RequestEvent {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($this->markReaction($locked, $by, CarbonImmutable::now())) {
                $locked->save();
            }

            return $this->log($locked, EventType::Comment, $by, $text);
        });
    }

    public function join(ServiceRequest $request, User $user): bool
    {
        return DB::transaction(function () use ($request, $user): bool {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->resident_user_id === $user->id || $locked->participants()->where('user_id', $user->id)->exists()) {
                return false;
            }
            $locked->participants()->attach($user->id, ['joined_at' => CarbonImmutable::now()]);
            $locked->participants_count += 1;
            $locked->save();
            $this->log($locked, EventType::ParticipantJoined, Actor::resident($user), null, ['user_id' => $user->id]);
            $this->publish($locked, new ParticipantJoined($locked, $user));

            return true;
        });
    }

    public function markOverdue(ServiceRequest $request): ?RequestEvent
    {
        return DB::transaction(function () use ($request): ?RequestEvent {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $locked->isOverdue() || $locked->hasOverdueMark()) {
                return null;
            }

            $event = $this->log($locked, EventType::Reminder, Actor::system(), null, [
                'kind' => RequestEvent::OVERDUE_KIND,
                'deadline_fix_at' => $locked->deadline_fix_at?->toIso8601String(),
            ]);
            $this->publish($locked, new RequestOverdue($locked, $event));

            return $event;
        });
    }

    public function markDueSoon(ServiceRequest $request, int $withinHours = 2): ?RequestEvent
    {
        return DB::transaction(function () use ($request, $withinHours): ?RequestEvent {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $now = CarbonImmutable::now();
            $deadline = $locked->deadline_fix_at;
            if (! $locked->status->isOpen() || $deadline === null || $deadline->lessThan($now) || $deadline->greaterThan($now->addHours($withinHours))) {
                return null;
            }
            if ($locked->events()->where('type', EventType::Reminder->value)->where('payload->kind', RequestEvent::DUE_SOON_KIND)->exists()) {
                return null;
            }

            $event = $this->log($locked, EventType::Reminder, Actor::system(), null, [
                'kind' => RequestEvent::DUE_SOON_KIND,
                'deadline_fix_at' => $deadline->toIso8601String(),
            ]);
            $this->publish($locked, new RequestDueSoon($locked, $event));

            return $event;
        });
    }

    public function rate(ServiceRequest $request, User $user, int $rating, ?string $comment = null): ServiceRequest
    {
        return DB::transaction(function () use ($request, $user, $rating, $comment): ServiceRequest {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->resident_user_id !== $user->id) {
                throw new NotAllowed('Оценить может только автор заявки');
            }
            if ($locked->status !== RequestStatus::Confirmed) {
                throw new NotAllowed('Оценить можно после подтверждения');
            }

            $value = max(1, min(5, $rating));
            $locked->rating = $value;
            $locked->rating_comment = $comment;
            $locked->save();
            $this->log($locked, EventType::Comment, Actor::resident($user), 'Оценка жителя: '.$value, ['rating' => $value]);

            return $locked;
        });
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{0:ServiceRequest,1:RequestEvent}
     */
    private function move(ServiceRequest $request, RequestStatus $to, Actor $by, ?string $comment = null, array $payload = []): array
    {
        $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
        $from = $locked->status;

        $this->ensureMayMove($locked, $to, $by, $comment);
        if (! $from->canTransitionTo($to)) {
            throw InvalidTransition::between($from, $to);
        }
        if ($to === RequestStatus::Confirmed) {
            $payload['confirmed_by'] = ConfirmedBy::forRole($by->role)->value;
        }

        $now = CarbonImmutable::now();
        $locked->status = $to;
        $this->stamp($locked, $to, $by, $now);
        $locked->save();

        $event = $this->log($locked, EventType::StatusChanged, $by, $comment, $payload, $from, $to);
        $this->publish($locked, new RequestStatusChanged($locked, $from, $to, $by, $event));

        return [$locked, $event];
    }

    private function ensureMayMove(ServiceRequest $request, RequestStatus $to, Actor $by, ?string $comment): void
    {
        if ($by->role === ActorRole::Resident) {
            if ($by->userId !== $request->resident_user_id) {
                throw new NotAllowed('Подтвердить или вернуть заявку может только тот, кто её подал');
            }
            if (! in_array($to, [RequestStatus::Confirmed, RequestStatus::Returned], true)) {
                throw new NotAllowed('Житель может только подтвердить решение или вернуть заявку в работу');
            }

            return;
        }

        if ($to === RequestStatus::Returned) {
            throw new NotAllowed('Вернуть заявку в работу может только житель, который её подал');
        }
        if ($to === RequestStatus::Confirmed && $by->role === ActorRole::Dispatcher && trim((string) $comment) === '') {
            throw new NotAllowed('Чтобы подтвердить заявку за жителя, напишите, как решение подтвердили');
        }
    }

    /** @return array{0:ResponsibleParty|null,1:string,2:string|null} */
    private function redirectTarget(ServiceRequest $request, RedirectData $data): array
    {
        $party = null;
        if ($data->partyId !== null) {
            $party = ResponsibleParty::query()->findOrFail($data->partyId);
            [$name, $phone] = [$party->name, $party->phone ?? $data->phone];
        } elseif ($data->contractorId !== null) {
            $contractor = Contractor::query()->where('organization_id', $request->organization_id)->findOrFail($data->contractorId);
            [$name, $phone] = [$contractor->name, $contractor->phone ?? $data->phone];
        } else {
            [$name, $phone] = [trim((string) $data->name), $data->phone];
        }

        if ($name === '') {
            throw new NotAllowed('Укажите, кому передана заявка');
        }

        return [$party, $name, $phone !== null && trim($phone) !== '' ? trim($phone) : null];
    }

    private function storeClosingPhoto(ServiceRequest $request, RequestEvent $event, ClosingPhoto $photo, string $disk, Actor $by): string
    {
        $original = file_get_contents($photo->file->getPathname());
        if ($original === false) {
            throw new RuntimeException('Не удалось прочитать фото закрытия');
        }
        $bytes = $this->images->sanitize($original, $photo->mime);
        unset($original);

        $path = sprintf('attachments/%d/%s.%s', $request->id, Str::uuid(), ImageSanitizer::extension($photo->mime));
        if (! Storage::disk($disk)->put($path, $bytes)) {
            throw new RuntimeException('Не удалось сохранить фото закрытия');
        }

        $request->attachments()->create([
            'event_id' => $event->id,
            'kind' => AttachmentKind::Closing,
            'disk' => $disk,
            'path' => $path,
            'mime' => $photo->mime,
            'size_bytes' => strlen($bytes),
            'uploaded_by' => $by->userId,
        ]);

        return $path;
    }

    private function stamp(ServiceRequest $request, RequestStatus $to, Actor $by, CarbonImmutable $now): void
    {
        $this->markReaction($request, $by, $now);

        match ($to) {
            RequestStatus::Assigned => $request->assigned_at = $now,
            RequestStatus::InProgress => $request->in_progress_at = $now,
            RequestStatus::Done => $request->done_at = $now,
            RequestStatus::Confirmed => [$request->confirmed_at = $now, $request->closed_at = $now],
            RequestStatus::Redirected => $request->closed_at = $now,
            RequestStatus::Returned => $request->returned_count += 1,
            RequestStatus::New => null,
        };

        if ($to === RequestStatus::Confirmed) {
            $request->confirmed_by = ConfirmedBy::forRole($by->role);
        }
    }

    private function markReaction(ServiceRequest $request, Actor $by, CarbonImmutable $now): bool
    {
        if (! $by->isDispatcher() || $request->first_reaction_at !== null) {
            return false;
        }
        $request->first_reaction_at = $now;

        return true;
    }

    private function publish(ServiceRequest $request, object $event): void
    {
        DB::afterCommit(function () use ($request, $event): void {
            try {
                event($event);
            } catch (Throwable $e) {
                report($e);
                Log::error('request.event_failed', ['request_id' => $request->id, 'event' => $event::class]);
            }
        });
    }

    /** @param array<string,mixed> $payload */
    private function log(ServiceRequest $request, EventType $type, Actor $by, ?string $comment, array $payload = [], ?RequestStatus $from = null, ?RequestStatus $to = null): RequestEvent
    {
        return $request->events()->create([
            'type' => $type,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'actor_user_id' => $by->userId,
            'actor_role' => $by->role,
            'comment' => $comment,
            'payload' => $payload,
        ]);
    }
}
