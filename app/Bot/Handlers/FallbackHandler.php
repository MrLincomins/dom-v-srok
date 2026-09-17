<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Updates\Update;
use App\Domain\Users\Models\User;

/**
 * любое сообщение непонятное - подсказка и меню, диалог не сбрасывается.
 * пока эхом
 */
final class FallbackHandler
{
    public function __construct(private readonly BotContext $ctx) {}

    public function handle(?Update $update, User $user): void
    {
        $text = $update?->text();
        if ($text !== null && $text !== '') {
            $this->ctx->reply($user, 'echo.reply', ['text' => mb_strimwidth($text, 0, 200, '…')]);

            return;
        }
        $this->ctx->reply($user, 'fallback.hint');
    }
}
