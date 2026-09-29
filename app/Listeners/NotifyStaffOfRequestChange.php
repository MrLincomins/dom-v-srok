<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Bot\Cards\RequestCard;
use App\Bot\Keyboards\Keyboards;
use App\Bot\Outbox\OutboxService;
use App\Bot\Texts\TextRepository;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Events\RequestCreated;
use App\Domain\Requests\Events\RequestStatusChanged;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;

final class NotifyStaffOfRequestChange
{
    public function __construct(
        private readonly OutboxService $outbox,
        private readonly TextRepository $texts,
        private readonly RequestCard $card,
    ) {}

    public function handleCreated(RequestCreated $event): void
    {
        $this->notify($event->request, 'request.new_staff', 'request.new_staff:'.$event->request->id, true);
    }

    public function handleStatusChanged(RequestStatusChanged $event): void
    {
        if ($event->to !== RequestStatus::Returned) {
            return;
        }
        $this->notify($event->request, 'request.returned_staff', 'request.returned_staff:'.$event->request->id.':'.$event->event->id, false);
    }

    private function notify(ServiceRequest $request, string $key, string $dedupe, bool $withDeadline): void
    {
        if ($request->organization_id === null || $this->createdByTestAccount($request)) {
            return;
        }
        $staff = $this->staffOf($request);
        if ($staff === []) {
            return;
        }

        $request->loadMissing(['category', 'house.region']);
        $vars = [
            'number' => $request->id,
            'what' => $this->card->what($request, true),
            'address' => $request->house->address.($request->entrance !== null ? ', подъезд '.$request->entrance : ''),
        ];
        if ($withDeadline) {
            $vars['deadline'] = $this->card->deadline($request);
        }

        foreach ($staff as $user) {
            $this->outbox->toUser((int) $user->max_user_id, $key, [
                'text' => $this->texts->text($key, $vars),
                'keyboard' => Keyboards::fromRows($this->texts->buttons($key, $vars)),
            ], $dedupe.':'.$user->id, $request->id);
        }
    }

    private function createdByTestAccount(ServiceRequest $request): bool
    {
        $author = $request->resident;

        return $author !== null && $author->is_demo && $author->max_user_id === null;
    }

    /** @return list<User> */
    private function staffOf(ServiceRequest $request): array
    {
        return User::query()
            ->where('organization_id', $request->organization_id)
            ->where('id', '!=', $request->resident_user_id)
            ->whereNotNull('max_user_id')
            ->whereNotNull('bot_started_at')
            ->get()
            ->filter(fn (User $user): bool => $user->isStaff())
            ->values()
            ->all();
    }
}
