<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;

final class FakeMaxClient extends MaxClient
{
    /** @var list<array{target:string,id:int,body:array<string,mixed>,mid?:string}> */
    public array $sent = [];

    /** @var list<string> */
    public array $answered = [];

    /** @var list<array{id:string,notification:string|null,message:array<string,mixed>|null}> */
    public array $answers = [];

    public bool $failEdits = false;

    /** @var list<array{chat:int,mid:string}> */
    public array $pinned = [];

    public bool $failPins = false;

    /** @var list<array{name:string,description?:string}> */
    public array $commands = [];

    /** @var list<string> */
    public array $uploaded = [];

    /** @var array<string,MaxApiException> */
    public array $uploadFailures = [];

    public function isConfigured(): bool
    {
        return true;
    }

    public function getMe(): array
    {
        return ['user_id' => 405671160, 'first_name' => 'Бот', 'username' => 'test_bot', 'is_bot' => true];
    }

    public function setCommands(array $commands): array
    {
        $this->commands = $commands;

        return ['commands' => $commands];
    }

    public ?MaxApiException $sendFailure = null;

    public function sendToUser(int $userId, array $body): array
    {
        if ($this->sendFailure !== null) {
            throw $this->sendFailure;
        }
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

    public function pinMessage(int $chatId, string $messageId, bool $notify = true): array
    {
        if ($this->failPins) {
            throw new MaxApiException('PUT /chats/pin → 403: bot is not an admin', 403, 'PUT /chats/pin');
        }
        $this->pinned[] = ['chat' => $chatId, 'mid' => $messageId];

        return ['success' => true];
    }

    public function uploadImage(string $path): string
    {
        if (isset($this->uploadFailures[basename($path)])) {
            throw $this->uploadFailures[basename($path)];
        }
        $this->uploaded[] = $path;

        return 'tok.'.count($this->uploaded);
    }

    public ?MaxApiException $answerFailure = null;

    public function answerCallback(string $callbackId, ?string $notification = null, ?array $message = null): array
    {
        if ($message !== null && $this->answerFailure !== null) {
            throw $this->answerFailure;
        }
        $this->answered[] = $callbackId;
        $this->answers[] = ['id' => $callbackId, 'notification' => $notification, 'message' => $message];
        if ($message !== null) {
            $this->sent[] = ['target' => 'answer', 'id' => 0, 'callback' => $callbackId, 'body' => $message];
        }

        return ['success' => true];
    }

    /** @return list<array{target:string,id:int,body:array<string,mixed>,mid?:string}> */
    public function messages(): array
    {
        return array_values(array_filter($this->sent, fn (array $m) => $m['target'] !== 'answer'));
    }

    public function texts(): array
    {
        return array_map(fn (array $m) => (string) ($m['body']['text'] ?? ''), $this->sent);
    }
}
