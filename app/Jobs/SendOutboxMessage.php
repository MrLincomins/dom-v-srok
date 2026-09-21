<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;
use App\Bot\Client\MaxRateLimited;
use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\Events\OutboxMessageSent;
use App\Bot\Outbox\OutboxStatus;
use App\Bot\Outbox\OutboxTarget;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** отправка одной строки outbox в мах, лимиты через RateLimited, ретраи с задержкой */
final class SendOutboxMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /** @var list<int> */
    public array $backoff = [2, 10, 30, 120, 600];

    public function __construct(public readonly int $outboxId) {}

    public function targetKey(): string
    {
        return OutboxMessage::query()->find($this->outboxId)?->targetKey() ?? 'unknown';
    }

    /**
     * лимиты только на настоящей очереди: sync не умеет откладывать задачи, сообщение зависло бы в pending
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        if (config('queue.default') === 'sync') {
            return [];
        }

        return [new RateLimited('max-target'), new RateLimited('max-global')];
    }

    public function handle(MaxClient $client): void
    {
        $message = OutboxMessage::query()->find($this->outboxId);
        if ($message === null || $message->status !== OutboxStatus::Pending) {
            return;
        }
        if ($message->available_at->isFuture()) {
            // отложенное сообщение (напоминание, сводка) - вернуть в очередь к нужному времени
            $this->release((int) ceil(CarbonImmutable::now()->diffInSeconds($message->available_at, true)));

            return;
        }

        if (! $client->isConfigured()) {
            $message->update(['status' => OutboxStatus::Skipped, 'last_error' => 'MAX_BOT_TOKEN не задан']);

            return;
        }

        $message->increment('attempts');

        try {
            $result = $this->deliver($client, $message);

            $message->update([
                'status' => OutboxStatus::Sent,
                'sent_at' => CarbonImmutable::now(),
                'max_message_id' => $result['message']['body']['mid'] ?? null,
                'last_error' => null,
            ]);
        } catch (MaxRateLimited $e) {
            $this->release(2);

            return;
        } catch (MaxApiException $e) {
            $message->update(['last_error' => $e->getMessage()]);
            if ($e->isPermanent()) {
                $message->update(['status' => OutboxStatus::Failed]);
                Log::warning('outbox.failed', ['outbox_id' => $message->id, 'error' => $e->getMessage()]);

                return;
            }
            throw $e; // временная ошибка, ретрай по backoff
        }

        event(new OutboxMessageSent($message));
    }

    /** @return array<string,mixed> */
    private function deliver(MaxClient $client, OutboxMessage $message): array
    {
        if ($message->edit_message_id !== null) {
            try {
                $client->editMessage($message->edit_message_id, $message->body);

                return ['message' => ['body' => ['mid' => $message->edit_message_id]]];
            } catch (MaxApiException $e) {
                if (! $e->isPermanent()) {
                    throw $e;
                }
                Log::info('outbox.edit_fallback', ['outbox_id' => $message->id, 'error' => $e->getMessage()]);
            }
        }

        return $message->target_type === OutboxTarget::Chat
            ? $client->sendToChat($message->target_id, $message->body)
            : $client->sendToUser($message->target_id, $message->body);
    }

    public function failed(\Throwable $e): void
    {
        OutboxMessage::query()->where('id', $this->outboxId)->where('status', OutboxStatus::Pending->value)
            ->update(['status' => OutboxStatus::Failed, 'last_error' => $e->getMessage()]);
    }
}
