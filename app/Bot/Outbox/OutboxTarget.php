<?php

declare(strict_types=1);

namespace App\Bot\Outbox;

enum OutboxTarget: string
{
    case User = 'user';
    case Chat = 'chat';
}
