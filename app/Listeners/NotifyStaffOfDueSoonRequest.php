<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Bot\Cards\RequestCard;
use App\Bot\Keyboards\Keyboards;
use App\Bot\Outbox\OutboxService;
use App\Bot\Texts\TextRepository;
use App\Domain\Requests\Events\RequestDueSoon;
use App\Domain\Users\Models\User;

final class NotifyStaffOfDueSoonRequest
{
    public function __construct(private readonly OutboxService $outbox, private readonly TextRepository $texts, private readonly RequestCard $card) {}

    public function handleDueSoon(RequestDueSoon $event): void
    {
        $request = $event->request;
        if ($request->organization === null) {
            return;
        }
        $request->loadMissing(['category', 'house.region']);
        $vars = [
            'number' => $request->id,
            'what' => $request->category->name,
            'address' => $request->house->address.($request->entrance !== null ? ', подъезд '.$request->entrance : ''),
            'deadline' => $this->card->deadline($request),
        ];

        $staff = $request->organization->staff()->whereNotNull('max_user_id')->whereNotNull('bot_started_at')->get()
            ->filter(fn (User $user) => $user->isStaff());
        foreach ($staff as $user) {
            $this->outbox->toUser((int) $user->max_user_id, 'request.due_soon_staff', [
                'text' => $this->texts->text('request.due_soon_staff', $vars),
                'keyboard' => Keyboards::fromRows($this->texts->buttons('request.due_soon_staff', $vars)),
            ], 'request.due_soon:'.$request->id.':'.$event->event->id.':'.$user->id, $request->id);
        }
    }
}
