<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\OutboxStatus;
use App\Jobs\SendOutboxMessage;
use Illuminate\Console\Command;

final class OutboxRequeueStale extends Command
{
    protected $signature = 'outbox:requeue-stale {--minutes=15 : Сколько минут сообщение может висеть в pending}';

    protected $description = 'снова поставить в очередь сообщения outbox, которые зависли в pending';

    public function handle(): int
    {
        $stale = OutboxMessage::query()
            ->where('status', OutboxStatus::Pending->value)
            ->where('available_at', '<=', now())
            ->where('updated_at', '<', now()->subMinutes((int) $this->option('minutes')))
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($stale as $message) {
            $message->touch();
            SendOutboxMessage::dispatch($message->id);
        }
        $this->info('Снова в очереди: '.$stale->count());

        return self::SUCCESS;
    }
}
