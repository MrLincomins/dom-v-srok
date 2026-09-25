<?php

declare(strict_types=1);

use App\Bot\Client\MaxClient;
use App\Bot\Outbox\OutboxService;
use App\Bot\Texts\TextRepository;
use App\Bot\UpdateDispatcher;
use App\Jobs\ProcessMaxUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeMaxClient;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

function asToken(string $token): TestCase
{
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

/**
 * @param  list<array<string,mixed>>  $attachments
 * @return array<string,mixed>
 */
function messageUpdate(int $userId, ?string $text, string $mid = 'mid.1', array $attachments = []): array
{
    return [
        'update_type' => 'message_created',
        'timestamp' => 1758100000000,
        'message' => [
            'sender' => ['user_id' => $userId, 'first_name' => 'Тест', 'last_name' => 'Житель', 'is_bot' => false],
            'recipient' => ['chat_type' => 'dialog', 'user_id' => $userId],
            'timestamp' => 1758100000000,
            'body' => ['mid' => $mid, 'seq' => 1, 'text' => $text, 'attachments' => $attachments],
        ],
        'user_locale' => 'ru',
    ];
}

/** @return array<string,mixed> */
function callbackUpdate(int $userId, string $payload, ?string $callbackId = null): array
{
    static $presses = 0;
    $callbackId ??= 'cb.auto.'.(++$presses);

    return [
        'update_type' => 'message_callback',
        'timestamp' => 1758100001000,
        'callback' => ['timestamp' => 1758100001000, 'callback_id' => $callbackId, 'payload' => $payload, 'user' => ['user_id' => $userId, 'first_name' => 'Тест', 'is_bot' => false]],
        'message' => [
            'sender' => ['user_id' => 405671160, 'first_name' => 'Бот', 'is_bot' => true],
            'recipient' => ['chat_type' => 'dialog', 'user_id' => $userId],
            'timestamp' => 1758100000500,
            'body' => ['mid' => 'mid.bot.'.$callbackId, 'seq' => 2, 'text' => '…'],
        ],
        'user_locale' => 'ru',
    ];
}

/** @return array<string,mixed> */
function chatMessageUpdate(int $userId, int $chatId, ?string $text, string $mid = 'mid.chat.1'): array
{
    $update = messageUpdate($userId, $text, $mid);
    $update['message']['recipient'] = ['chat_type' => 'chat', 'chat_id' => $chatId];

    return $update;
}

/** @return array<string,mixed> */
function chatCallbackUpdate(int $userId, int $chatId, string $payload, string $callbackId = 'cb.chat.1'): array
{
    $update = callbackUpdate($userId, $payload, $callbackId);
    $update['message']['recipient'] = ['chat_type' => 'chat', 'chat_id' => $chatId];

    return $update;
}

/** @return array<string,mixed> */
function startUpdate(int $userId, ?string $payload = null): array
{
    return [
        'update_type' => 'bot_started',
        'timestamp' => 1758100000000,
        'chat_id' => $userId,
        'user' => ['user_id' => $userId, 'first_name' => 'Анна'],
        'payload' => $payload,
    ];
}

/** @param array<string,mixed> $raw */
function runUpdate(array $raw): void
{
    (new ProcessMaxUpdate($raw))->handle(app(UpdateDispatcher::class), app(OutboxService::class));
}

function fakeMax(): FakeMaxClient
{
    $fake = new FakeMaxClient;
    app()->instance(MaxClient::class, $fake);

    return $fake;
}

function lastText(FakeMaxClient $max): string
{
    $last = end($max->sent);

    return $last === false ? '' : (string) ($last['body']['text'] ?? '');
}

/** @return list<string> */
function lastButtons(FakeMaxClient $max): array
{
    $last = end($max->sent);
    if ($last === false) {
        return [];
    }
    $payloads = [];
    foreach ($last['body']['keyboard'][0]['payload']['buttons'] ?? [] as $row) {
        foreach ($row as $button) {
            $payloads[] = (string) ($button['payload'] ?? $button['url'] ?? $button['web_app'] ?? '');
        }
    }

    return $payloads;
}

/** @return array{id:string,notification:string|null,message:array<string,mixed>|null} */
function lastAnswer(FakeMaxClient $max): array
{
    $last = end($max->answers);

    return $last === false ? ['id' => '', 'notification' => null, 'message' => null] : $last;
}

/** @param array<string,string|int|null> $vars */
function botText(string $key, array $vars = []): string
{
    return app(TextRepository::class)->text($key, $vars);
}
