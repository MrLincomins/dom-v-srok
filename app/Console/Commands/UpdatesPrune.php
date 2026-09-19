<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bot\Models\OutboxMessage;
use App\Bot\Models\ProcessedUpdate;
use App\Bot\Outbox\OutboxStatus;
use Illuminate\Console\Command;

final class UpdatesPrune extends Command
{
    protected $signature = 'updates:prune';

    protected $description = 'удалить ключи обновлений старше 7 дней и отправленные сообщения outbox старше 30 дней';

    public function handle(): int
    {
        $keys = ProcessedUpdate::query()->where('received_at', '<', now()->subDays(7))->delete();
        $sent = OutboxMessage::query()
            ->where('status', OutboxStatus::Sent->value)
            ->where('sent_at', '<', now()->subDays(30))
            ->delete();
        $this->info("Удалено ключей: {$keys}, сообщений: {$sent}");

        return self::SUCCESS;
    }
}
