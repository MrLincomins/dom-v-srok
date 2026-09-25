<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Bot\Cards\RequestCard;
use App\Bot\Keyboards\Keyboards;
use App\Bot\Outbox\OutboxService;
use App\Bot\Texts\TextRepository;
use App\Domain\Requests\Events\MorningDigestDue;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;

final class NotifyStaffOfMorningDigest
{
    public function __construct(private readonly OutboxService $outbox, private readonly TextRepository $texts, private readonly RequestCard $card) {}

    public function handleMorningDigest(MorningDigestDue $event): void
    {
        $organization = $event->organization;
        $list = $event->top
            ->map(fn (ServiceRequest $request) => '№ '.$request->id.': '.$this->card->what($request, true).', '.$this->card->deadline($request))
            ->implode('; ');
        $vars = [
            ...$event->counters,
            'top' => $list !== '' ? $this->texts->text('digest.morning_top', ['list' => $list]) : $this->texts->text('digest.morning_empty'),
        ];
        $date = $event->localDate->toDateString();

        $staff = $organization->staff()->whereNotNull('max_user_id')->whereNotNull('bot_started_at')->get()
            ->filter(fn (User $user) => $user->isStaff());
        foreach ($staff as $user) {
            $this->outbox->toUser((int) $user->max_user_id, 'digest.morning', [
                'text' => $this->texts->text('digest.morning', $vars),
                'keyboard' => Keyboards::fromRows($this->texts->buttons('digest.morning', $vars)),
            ], 'digest.morning:'.$organization->id.':'.$user->id.':'.$date);
        }
    }
}
