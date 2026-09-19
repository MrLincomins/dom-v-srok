<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;

/** клиент без сети, запоминает что бот отправил. подменяется в контейнере в тестах */
final class FakeMaxClient extends MaxClient
{
    /** @var list<array{target:string,id:int,body:array<string,mixed>,mid?:string}> */
    public array $sent = [];

    /** @var list<string> */
    public array $answered = [];

    public bool $failEdits = false;

    public function isConfigured(): bool
    {
        return true;
    }

    public function sendToUser(int $userId, array $body): array
    {
        $this->sent[] = ['target' => 'user', 'id' => $userId, 'body' => $body];

        return ['message' => ['body' => ['mid' => 'mid.'.count($this->sent)]]];
    }

    public function sendToChat(int $chatId, array $body): array
    {
        $this->sent[] = ['target' => 'chat', 'id' => $chatId, 'body' => $body];

        return ['message' => ['body' => ['mid' => 'mid.'.count($this->sent)]]];
    }

    public function editMessage(string $messageId, array $body): array
    {
        if ($this->failEdits) {
            throw new MaxApiException('PUT /messages → 400: message not found', 400, 'PUT /messages');
        }
        $this->sent[] = ['target' => 'edit', 'id' => 0, 'mid' => $messageId, 'body' => $body];

        return ['success' => true];
    }

    public function answerCallback(string $callbackId, ?string $notification = null, ?array $message = null): array
    {
        $this->answered[] = $callbackId;

        return ['success' => true];
    }

    public function texts(): array
    {
        return array_map(fn (array $m) => (string) ($m['body']['text'] ?? ''), $this->sent);
    }
}
