<?php

declare(strict_types=1);

namespace App\Bot\Fsm;

use App\Bot\Models\BotSession;
use Carbon\CarbonImmutable;

/** состояние диалога в bot_sessions, черновик старше суток забываем */
final class SessionStore
{
    private const TTL_HOURS = 24;

    public function load(int $maxUserId): BotSession
    {
        $session = BotSession::query()->find($maxUserId);
        if ($session === null) {
            return new BotSession(['max_user_id' => $maxUserId, 'state' => DialogState::Idle, 'payload' => []]);
        }
        if ($session->state !== DialogState::Idle && $session->updated_at->lessThan(CarbonImmutable::now()->subHours(self::TTL_HOURS))) {
            $session->state = DialogState::Idle;
            $session->payload = [];
            $session->save();
        }

        return $session;
    }

    /** @param array<string,mixed> $payload */
    public function save(int $maxUserId, DialogState $state, array $payload): void
    {
        BotSession::query()->updateOrCreate(['max_user_id' => $maxUserId], ['state' => $state, 'payload' => $payload]);
    }

    public function reset(int $maxUserId): void
    {
        $session = BotSession::query()->find($maxUserId);
        if ($session === null) {
            return;
        }
        $session->state = DialogState::Idle;
        $session->payload = [];
        $session->save();
    }
}
