<?php

declare(strict_types=1);

namespace App\Bot\Updates;

/**
 * обновления маха.
 * message_created: message.sender, message.recipient{chat_id, chat_type, user_id}, message.body{mid, text, attachments}
 * message_callback: callback{callback_id, payload, user}, message
 * bot_started: chat_id, user, payload (из deep link ?start=)
 */
final readonly class Update
{
    /** @param array<string,mixed> $raw */
    public function __construct(public string $type, public int $timestamp, public array $raw) {}

    /** @param array<string,mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self((string) ($raw['update_type'] ?? 'unknown'), (int) ($raw['timestamp'] ?? 0), $raw);
    }

    /** ключ идемпотентности: тип + идентификатор сообщения/кнопки + время. */
    public function key(): string
    {
        $id = $this->raw['message']['body']['mid']
            ?? $this->raw['callback']['callback_id']
            ?? (($this->raw['chat_id'] ?? '').'-'.($this->raw['user']['user_id'] ?? ''));

        return mb_substr($this->type.':'.$id.':'.$this->timestamp, 0, 120);
    }

    // сообщение

    public function isMessage(): bool
    {
        return $this->type === 'message_created' && isset($this->raw['message']);
    }

    public function text(): ?string
    {
        $text = $this->raw['message']['body']['text'] ?? null;

        return is_string($text) ? trim($text) : null;
    }

    public function messageId(): ?string
    {
        return $this->raw['message']['body']['mid'] ?? null;
    }

    /** @return list<array<string,mixed>> */
    public function attachments(): array
    {
        $list = $this->raw['message']['body']['attachments'] ?? [];

        return is_array($list) ? array_values($list) : [];
    }

    public function chatType(): ?string
    {
        $type = $this->raw['message']['recipient']['chat_type'] ?? null;

        return is_string($type) ? strtolower($type) : null;
    }

    public function isPrivate(): bool
    {
        return $this->chatType() === 'dialog' || ($this->chatType() === null && $this->chatId() === null);
    }

    public function chatId(): ?int
    {
        $id = $this->raw['message']['recipient']['chat_id'] ?? $this->raw['chat_id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    // пользователь

    /** @return array<string,mixed>|null объект User из API: user_id, first_name, last_name, username */
    public function user(): ?array
    {
        $user = $this->raw['callback']['user'] ?? $this->raw['message']['sender'] ?? $this->raw['user'] ?? null;

        return is_array($user) ? $user : null;
    }

    public function userId(): ?int
    {
        $id = $this->user()['user_id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    // кнопка

    public function isCallback(): bool
    {
        return $this->type === 'message_callback' && isset($this->raw['callback']);
    }

    public function callbackId(): ?string
    {
        return $this->raw['callback']['callback_id'] ?? null;
    }

    public function callbackPayload(): ?string
    {
        $payload = $this->raw['callback']['payload'] ?? null;

        return is_string($payload) ? $payload : null;
    }

    // запуск бота

    public function startPayload(): ?string
    {
        $payload = $this->raw['payload'] ?? null;

        return is_string($payload) && $payload !== '' ? $payload : null;
    }
}
