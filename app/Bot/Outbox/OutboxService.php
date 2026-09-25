<?php

declare(strict_types=1);

namespace App\Bot\Outbox;

use App\Bot\Models\OutboxMessage;
use App\Jobs\SendOutboxMessage;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class OutboxService
{
    private ?int $captureUserId = null;

    private ?string $captureMessageId = null;

    private ?OutboxMessage $captured = null;

    public function captureNextForUser(int $maxUserId, ?string $messageId): void
    {
        $this->captureUserId = $maxUserId;
        $this->captureMessageId = $messageId;
        $this->captured = null;
    }

    public function takeCaptured(): ?OutboxMessage
    {
        $message = $this->captured;
        $this->captureUserId = null;
        $this->captureMessageId = null;
        $this->captured = null;

        return $message;
    }

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
        ?string $editMessageId = null,
        bool $dispatch = true,
    ): ?OutboxMessage {
        try {
            $message = DB::transaction(fn () => OutboxMessage::query()->create([
                'target_type' => $target,
                'target_id' => $targetId,
                'kind' => $kind,
                'body' => $body,
                'request_id' => $requestId,
                'dedupe_key' => $dedupeKey,
                'edit_message_id' => $editMessageId,
                'status' => OutboxStatus::Pending,
                'available_at' => $availableAt ?? CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        if ($dispatch) {
            $this->dispatch($message);
        }

        return $message;
    }

    public function dispatch(OutboxMessage $message): void
    {
        $job = SendOutboxMessage::dispatch($message->id)->afterCommit();
        if ($message->available_at->isFuture()) {
            $job->delay($message->available_at);
        }
    }

    /** @param array<string,mixed> $body */
    public function toUser(int $maxUserId, string $kind, array $body, ?string $dedupeKey = null, ?int $requestId = null, ?CarbonImmutable $availableAt = null): ?OutboxMessage
    {
        $capture = $this->captureUserId === $maxUserId && ($availableAt === null || ! $availableAt->isFuture());
        $message = $this->enqueue(
            OutboxTarget::User,
            $maxUserId,
            $kind,
            $body,
            $dedupeKey,
            $requestId,
            $availableAt,
            $capture ? $this->captureMessageId : null,
            ! $capture,
        );
        if ($capture && $message !== null) {
            $this->captured = $message;
            $this->captureUserId = null;
        }

        return $message;
    }

    /** @param array<string,mixed> $body */
    public function toChat(int $chatId, string $kind, array $body, ?string $dedupeKey = null, ?int $requestId = null): ?OutboxMessage
    {
        return $this->enqueue(OutboxTarget::Chat, $chatId, $kind, $body, $dedupeKey, $requestId);
    }
}
