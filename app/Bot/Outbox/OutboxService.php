<?php

declare(strict_types=1);

namespace App\Bot\Outbox;

use App\Bot\Models\OutboxMessage;
use App\Jobs\SendOutboxMessage;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * всё что выходит наружу
 */
final class OutboxService
{
    /**
     * @param  array<string,mixed>  $body  {text, keyboard?: list<list<array>>, attachments?: list<array>, format?: string, notify?: bool}
     */
    public function enqueue(
        OutboxTarget $target,
        int $targetId,
        string $kind,
        array $body,
        ?string $dedupeKey = null,
        ?int $requestId = null,
        ?CarbonImmutable $availableAt = null,
    ): ?OutboxMessage {
        try {
            $message = OutboxMessage::query()->create([
                'target_type' => $target,
                'target_id' => $targetId,
                'kind' => $kind,
                'body' => $body,
                'request_id' => $requestId,
                'dedupe_key' => $dedupeKey,
                'status' => OutboxStatus::Pending,
                'available_at' => $availableAt ?? CarbonImmutable::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return null; // такое сообщение уже поставлено в очередь
        }

        $job = SendOutboxMessage::dispatch($message->id)->afterCommit();
        if ($availableAt !== null && $availableAt->isFuture()) {
            $job->delay($availableAt);
        }

        return $message;
    }

    public function toUser(int $maxUserId, string $kind, array $body, ?string $dedupeKey = null, ?int $requestId = null, ?CarbonImmutable $availableAt = null): ?OutboxMessage
    {
        return $this->enqueue(OutboxTarget::User, $maxUserId, $kind, $body, $dedupeKey, $requestId, $availableAt);
    }

    public function toChat(int $chatId, string $kind, array $body, ?string $dedupeKey = null, ?int $requestId = null): ?OutboxMessage
    {
        return $this->enqueue(OutboxTarget::Chat, $chatId, $kind, $body, $dedupeKey, $requestId);
    }
}
