<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Keyboards\Keyboards;
use App\Bot\Outbox\OutboxService;
use App\Bot\Texts\TextRepository;
use App\Domain\Users\Models\User;

/** общие действия, например ответить пользователю текстом из таблицы с кнопками */
final class BotContext
{
    public function __construct(
        private readonly OutboxService $outbox,
        private readonly TextRepository $texts,
    ) {}

    /** @param array<string,string|int|null> $vars */
    public function reply(User $user, string $key, array $vars = [], ?string $dedupeKey = null): void
    {
        if ($user->max_user_id === null) {
            return;
        }
        $this->outbox->toUser($user->max_user_id, 'bot.'.$key, [
            'text' => $this->texts->text($key, $vars),
            'keyboard' => Keyboards::fromRows($this->texts->buttons($key, $vars)),
        ], $dedupeKey);
    }

    /**
     * текст из таблицы плюс свои кнопки перед кнопками из таблицы
     *
     * @param  array<string,string|int|null>  $vars
     * @param  list<list<array{label:string,action:string}>>  $rows
     */
    public function replyWith(User $user, string $key, array $vars = [], array $rows = []): void
    {
        if ($user->max_user_id === null) {
            return;
        }
        $this->outbox->toUser($user->max_user_id, 'bot.'.$key, [
            'text' => $this->texts->text($key, $vars),
            'keyboard' => Keyboards::fromRows([...$rows, ...$this->texts->buttons($key, $vars)]),
        ]);
    }

    /** @param list<list<array{label:string,action:string}>> $rows */
    public function replyRaw(User $user, string $text, array $rows = []): void
    {
        if ($user->max_user_id === null) {
            return;
        }
        $this->outbox->toUser($user->max_user_id, 'bot.raw', ['text' => $text, 'keyboard' => Keyboards::fromRows($rows)]);
    }

    public function texts(): TextRepository
    {
        return $this->texts;
    }

    public function outbox(): OutboxService
    {
        return $this->outbox;
    }
}
