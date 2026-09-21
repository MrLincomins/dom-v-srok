<?php

declare(strict_types=1);

namespace App\Bot\Outbox\Events;

use App\Bot\Models\OutboxMessage;

final readonly class OutboxMessageSent
{
    public function __construct(public OutboxMessage $message) {}
}
