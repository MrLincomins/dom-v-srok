<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Domain\Users\Models\User;

/** непонятное сообщение: подсказка и меню, черновик не трогаем */
final class FallbackHandler
{
    public function __construct(private readonly BotContext $ctx) {}

    public function handle(User $user): void
    {
        $this->ctx->reply($user, 'fallback.hint');
    }
}
