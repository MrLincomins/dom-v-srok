<?php

declare(strict_types=1);

namespace App\Bot;

use App\Support\Models\AppSetting;

final class BotIdentity
{
    /** @param array<string,mixed> $me */
    public function remember(array $me): void
    {
        AppSetting::put('bot', [
            'id' => isset($me['user_id']) ? (int) $me['user_id'] : null,
            'username' => isset($me['username']) ? (string) $me['username'] : null,
        ]);
    }

    public function id(): ?int
    {
        $id = AppSetting::get('bot')['id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    public function username(): string
    {
        return (string) config('max.bot_username');
    }
}
