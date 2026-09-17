<?php

declare(strict_types=1);

namespace App\Bot\Outbox;

enum OutboxStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
