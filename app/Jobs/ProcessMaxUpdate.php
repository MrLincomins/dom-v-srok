<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bot\Models\ProcessedUpdate;
use App\Bot\Outbox\OutboxService;
use App\Bot\UpdateDispatcher;
use App\Bot\Updates\Update;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessMaxUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30];

    public const QUEUE = 'updates';

    /** @param array<string,mixed> $raw */
    public function __construct(public readonly array $raw)
    {
        $this->onQueue(self::QUEUE);
    }

    public function handle(UpdateDispatcher $dispatcher, OutboxService $outbox): void
    {
        $update = Update::fromArray($this->raw);
        $started = CarbonImmutable::now();
        Log::info('bot.update', ['type' => $update->type, 'key' => $update->key()]);
        $outbox->beginUpdate($update->key());
        try {
            $dispatcher->dispatch($update);
        } finally {
            $outbox->endUpdate();
            $this->logLatency($update, $started);
        }
    }

    private function logLatency(Update $update, CarbonImmutable $started): void
    {
        $receivedAt = ProcessedUpdate::query()->whereKey($update->key())->value('received_at');
        $eventMs = $update->timestamp > 100_000_000_000 ? $update->timestamp : $update->timestamp * 1000;
        $received = $receivedAt instanceof CarbonImmutable ? $receivedAt : $started;
        Log::info('bot.latency', [
            'type' => $update->type,
            'delivery_ms' => max(0, (int) ($received->getTimestampMs() - $eventMs)),
            'queue_ms' => max(0, (int) ($started->getTimestampMs() - $received->getTimestampMs())),
            'handling_ms' => (int) (CarbonImmutable::now()->getTimestampMs() - $started->getTimestampMs()),
        ]);
    }
}
