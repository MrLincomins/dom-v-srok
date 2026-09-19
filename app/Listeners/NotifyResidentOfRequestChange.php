<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Bot\Cards\RequestCard;
use App\Bot\Keyboards\Keyboards;
use App\Bot\Outbox\OutboxService;
use App\Bot\Texts\TextRepository;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Events\RequestCreated;
use App\Domain\Requests\Events\RequestStatusChanged;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;

/** каждая смена статуса - сообщение жителю с тем же номером, только тем кто запускал бота. dedupe_key защищает от дублей. дому без организации ещё напоминание в срок */
final class NotifyResidentOfRequestChange
{
    public function __construct(
        private readonly OutboxService $outbox,
        private readonly TextRepository $texts,
        private readonly RequestCard $card,
    ) {}

    public function handleCreated(RequestCreated $event): void
    {
        $request = $event->request;
        $resident = $request->resident;
        if (! $resident->canBeMessaged()) {
            return;
        }

        $this->outbox->toUser($resident->max_user_id, 'request.created', [
            'text' => $this->card->created($request),
            'keyboard' => Keyboards::fromRows($this->texts->buttons('request.created', ['number' => $request->id])),
        ], 'request.created:'.$request->id, $request->id);

        $deadline = $request->deadline_fix_at ?? $request->deadline_reply_at;
        if ($request->organization_id !== null || $deadline === null) {
            return;
        }
        $vars = ['number' => $request->id, 'escalation' => (string) $request->house->region->escalation_text];
        $this->outbox->toUser($resident->max_user_id, 'request.reminder', [
            'text' => $this->texts->text('request.reminder', $vars),
            'keyboard' => Keyboards::fromRows($this->texts->buttons('request.reminder', $vars)),
        ], 'request.reminder:'.$request->id, $request->id, $deadline);
    }

    public function handleStatusChanged(RequestStatusChanged $event): void
    {
        $request = $event->request;
        $resident = $request->resident;
        if (! $resident->canBeMessaged()) {
            return;
        }

        [$key, $vars] = $this->messageFor($request, $event->to, $event->event->comment);
        $this->outbox->toUser($resident->max_user_id, 'request.status', [
            'text' => $this->texts->text($key, $vars),
            'keyboard' => Keyboards::fromRows($this->texts->buttons($key, $vars)),
        ], 'request.status:'.$request->id.':'.$event->to->value.':'.$event->event->id, $request->id);

        $this->notifyParticipants($request, $event->to, $resident);
    }

    /** @return array{0:string,1:array<string,string|int|null>} */
    private function messageFor(ServiceRequest $request, RequestStatus $to, ?string $comment): array
    {
        $vars = ['number' => $request->id, 'status' => $to->label(), 'comment' => $comment ?? ''];

        return match ($to) {
            RequestStatus::Done => ['request.done_confirm', $vars],
            RequestStatus::Confirmed => [$request->confirmed_by === ConfirmedBy::Auto ? 'request.auto_confirmed' : 'request.confirmed', $vars],
            RequestStatus::Returned => ['request.returned', $vars],
            RequestStatus::Redirected => ['request.redirected', $vars + [
                'to' => $request->redirectedParty->name ?? (string) ($request->events()->latest('id')->first()?->payload['to'] ?? ''),
                'phone' => $request->redirectedParty->phone ?? '—',
            ]],
            default => ['request.status', $vars],
        };
    }

    private function notifyParticipants(ServiceRequest $request, RequestStatus $to, User $resident): void
    {
        if (! in_array($to, [RequestStatus::Done, RequestStatus::Confirmed, RequestStatus::Redirected], true)) {
            return;
        }
        foreach ($request->participants as $participant) {
            if ($participant->id === $resident->id || ! $participant->canBeMessaged()) {
                continue;
            }
            $this->outbox->toUser($participant->max_user_id, 'request.status', [
                'text' => $this->texts->text('request.status', ['number' => $request->id, 'status' => $to->label(), 'comment' => '']),
            ], 'request.status:'.$request->id.':'.$to->value.':p'.$participant->id, $request->id);
        }
    }
}
