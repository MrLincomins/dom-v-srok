<?php

declare(strict_types=1);

namespace App\Bot;

use App\Bot\Handlers\CallbackHandler;
use App\Bot\Handlers\ChatHandler;
use App\Bot\Handlers\CommandHandler;
use App\Bot\Handlers\StartHandler;
use App\Bot\Updates\Update;
use App\Domain\Users\Models\User;
use App\Domain\Users\UserService;
use Illuminate\Support\Facades\Log;

final class UpdateDispatcher
{
    public function __construct(
        private readonly UserService $users,
        private readonly StartHandler $start,
        private readonly CommandHandler $commands,
        private readonly CallbackHandler $callbacks,
        private readonly ChatHandler $chat,
    ) {}

    public function dispatch(Update $update): void
    {
        $maxUser = $update->user();

        match (true) {
            $update->type === 'bot_started' => $this->start->handle($update, $this->user($maxUser, true), $update->startPayload()),
            $update->isCallback() => $this->callbacks->handle($update, $this->user($maxUser, $update->chatType() === 'dialog')),
            $update->isMessage() && $update->isPrivate() => $this->commands->handle($update, $this->user($maxUser, true)),
            $update->isMessage() => $this->chat->handle($update, $this->userIfKnown($maxUser)),
            $update->type === 'bot_stopped', $update->type === 'dialog_removed' => $this->stopped($maxUser),
            default => Log::info('bot.update_ignored', ['type' => $update->type]),
        };
    }

    /** @param array<string,mixed>|null $maxUser */
    private function user(?array $maxUser, bool $startedBot): User
    {
        if ($maxUser === null) {
            throw new \RuntimeException('Обновление без пользователя: '.json_encode($maxUser));
        }

        return $this->users->upsertFromMax($maxUser, $startedBot);
    }

    /** @param array<string,mixed>|null $maxUser */
    private function userIfKnown(?array $maxUser): ?User
    {
        $id = $maxUser['user_id'] ?? null;

        return $id === null ? null : User::query()->where('max_user_id', (int) $id)->first();
    }

    /** @param array<string,mixed>|null $maxUser */
    private function stopped(?array $maxUser): void
    {
        $id = $maxUser['user_id'] ?? null;
        if ($id !== null) {
            User::query()->where('max_user_id', (int) $id)->update(['bot_started_at' => null]);
        }
    }
}
