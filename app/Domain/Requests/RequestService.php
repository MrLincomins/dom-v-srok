<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Catalog\Models\ResponsibleParty;
use App\Domain\Catalog\ResponsibleResolver;
use App\Domain\Organizations\Models\Executor;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Dto\RedirectData;
use App\Domain\Requests\Enums\ActorRole;
use App\Domain\Requests\Enums\AttachmentKind;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Events\ParticipantJoined;
use App\Domain\Requests\Events\RequestCreated;
use App\Domain\Requests\Events\RequestOverdue;
use App\Domain\Requests\Events\RequestStatusChanged;
use App\Domain\Requests\Exceptions\EmergencyCategory;
use App\Domain\Requests\Exceptions\InvalidTransition;
use App\Domain\Requests\Exceptions\NotAllowed;
use App\Domain\Requests\Models\RequestEvent;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RequestService
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ResponsibleResolver $resolver,
        private readonly DeadlineCalculator $deadlines,
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

    public function transition(ServiceRequest $request, RequestStatus $to, Actor $by, ?string $comment = null, array $payload = []): ServiceRequest
    {
        return DB::transaction(function () use ($request, $to, $by, $comment, $payload): ServiceRequest {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw InvalidTransition::between($from, $to);
            }

            $now = CarbonImmutable::now();
            $locked->status = $to;
            $this->stamp($locked, $to, $by, $now);
            $locked->save();

            $event = $this->log($locked, EventType::StatusChanged, $by, $comment, $payload, $from, $to);
            $this->publish($locked, new RequestStatusChanged($locked, $from, $to, $by, $event));

            return $locked;
        });
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

            $locked->executor_id = $executor->id;
            $locked->assigned_at = CarbonImmutable::now();
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

    public function close(ServiceRequest $request, Actor $by, ?string $comment = null): ServiceRequest
    {
        return $this->transition($request, RequestStatus::Done, $by, $comment);
    }

    public function confirm(ServiceRequest $request, Actor $by, ConfirmedBy $how, ?string $comment = null): ServiceRequest
    {
        return $this->transition($request, RequestStatus::Confirmed, $by, $comment, ['confirmed_by' => $how->value]);
    }

    public function returnToWork(ServiceRequest $request, Actor $by, ?string $comment = null): ServiceRequest
    {
        return $this->transition($request, RequestStatus::Returned, $by, $comment);
    }

    public function redirect(ServiceRequest $request, Actor $by, RedirectData $data): ServiceRequest
    {
        $party = $data->partyId !== null ? ResponsibleParty::query()->findOrFail($data->partyId) : null;
        $name = $party->name ?? $data->name;
        if ($name === null || $name === '') {
            throw new NotAllowed('Укажите, кому переадресована заявка');
        }

        return DB::transaction(function () use ($request, $by, $data, $party, $name): ServiceRequest {
            $locked = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $locked->status->canTransitionTo(RequestStatus::Redirected)) {
                throw InvalidTransition::between($locked->status, RequestStatus::Redirected);
            }

            $locked->redirected_party_id = $party?->id;
            $locked->redirect_note = $data->note;
            $locked->save();

            return $this->transition($locked, RequestStatus::Redirected, $by, $data->note, [
                'to' => $name,
                'phone' => $party->phone ?? $data->phone,
            ]);
        });
    }

    public function comment(ServiceRequest $request, Actor $by, string $text): RequestEvent
    {
        return $this->log($request, EventType::Comment, $by, $text);
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

    private function stamp(ServiceRequest $request, RequestStatus $to, Actor $by, CarbonImmutable $now): void
    {
        if ($by->isDispatcher() && $request->first_reaction_at === null) {
            $request->first_reaction_at = $now;
        }

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
            $request->confirmed_by = match ($by->role) {
                ActorRole::Resident => ConfirmedBy::Resident,
                ActorRole::Dispatcher => ConfirmedBy::Dispatcher,
                ActorRole::System => ConfirmedBy::Auto,
            };
        }
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
