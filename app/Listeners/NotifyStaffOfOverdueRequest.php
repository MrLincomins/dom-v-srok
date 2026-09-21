<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Bot\Keyboards\Keyboards;
use App\Bot\Outbox\OutboxService;
use App\Bot\Texts\TextRepository;
use App\Domain\Requests\Events\RequestOverdue;
use App\Domain\Users\Models\User;

final class NotifyStaffOfOverdueRequest
{
    public function __construct(private readonly OutboxService $outbox, private readonly TextRepository $texts) {}

    public function handleOverdue(RequestOverdue $event): void
    {
        $request = $event->request;
        if ($request->organization === null) {
            return;
        }
        $request->loadMissing(['category', 'house']);
        $vars = [
            'number' => $request->id,
            'what' => $request->category->name,
            'address' => $request->house->address.($request->entrance !== null ? ', подъезд '.$request->entrance : ''),
        ];

        $staff = $request->organization->staff()->whereNotNull('max_user_id')->whereNotNull('bot_started_at')->get()
            ->filter(fn (User $user) => $user->isStaff());
        foreach ($staff as $user) {
            $this->outbox->toUser((int) $user->max_user_id, 'request.overdue_staff', [
                'text' => $this->texts->text('request.overdue_staff', $vars),
                'keyboard' => Keyboards::fromRows($this->texts->buttons('request.overdue_staff', $vars)),
            ], 'request.overdue:'.$request->id.':'.$event->event->id.':'.$user->id, $request->id);
        }
    }
}
