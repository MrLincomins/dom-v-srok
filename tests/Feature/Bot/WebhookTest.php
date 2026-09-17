<?php

declare(strict_types=1);

use App\Bot\Client\MaxClient;
use App\Bot\Texts\TextRepository;
use App\Bot\UpdateDispatcher;
use App\Jobs\ProcessMaxUpdate;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeMaxClient;

function messageUpdate(int $userId, string $text, string $mid = 'mid.1'): array
{
    return [
        'update_type' => 'message_created',
        'timestamp' => 1758100000000,
        'message' => [
            'sender' => ['user_id' => $userId, 'first_name' => 'Тест', 'last_name' => 'Житель', 'is_bot' => false],
            'recipient' => ['chat_type' => 'dialog', 'user_id' => $userId],
            'timestamp' => 1758100000000,
            'body' => ['mid' => $mid, 'seq' => 1, 'text' => $text],
        ],
        'user_locale' => 'ru',
    ];
}

it('rejects a webhook call without the secret', function () {
    $this->postJson('/max/webhook', messageUpdate(1, 'привет'))->assertStatus(403);
    $this->postJson('/max/webhook', messageUpdate(1, 'привет'), ['X-Max-Bot-Api-Secret' => 'wrong'])->assertStatus(403);
});

it('accepts an update once and ignores the duplicate', function () {
    Queue::fake();
    $headers = ['X-Max-Bot-Api-Secret' => 'test-webhook-secret'];

    $this->postJson('/max/webhook', messageUpdate(1, 'привет'), $headers)->assertOk()->assertJsonPath('duplicate', false);
    $this->postJson('/max/webhook', messageUpdate(1, 'привет'), $headers)->assertOk()->assertJsonPath('duplicate', true);

    Queue::assertPushed(ProcessMaxUpdate::class, 1);
    $this->assertDatabaseCount('processed_updates', 1);
});

it('replies to /start with the greeting and menu, and echoes free text', function () {
    $this->seed(DatabaseSeeder::class);
    $fake = new FakeMaxClient;
    $this->app->instance(MaxClient::class, $fake);

    $start = ['update_type' => 'bot_started', 'timestamp' => 1758100000000, 'chat_id' => 7, 'user' => ['user_id' => 555, 'first_name' => 'Анна'], 'payload' => 'h_'.DemoSeeder::HOUSE_QR_TOKEN.'_2'];
    (new ProcessMaxUpdate($start))->handle(app(UpdateDispatcher::class));

    $this->assertDatabaseHas('users', ['max_user_id' => 555, 'entrance' => 2]);
    expect($fake->texts())->toContain(app(TextRepository::class)->text('start.greeting'));
    expect(implode("\n", $fake->texts()))->toContain(DemoSeeder::HOUSE_ADDRESS);

    (new ProcessMaxUpdate(messageUpdate(555, 'не горит свет', 'mid.2')))->handle(app(UpdateDispatcher::class));
    expect(end($fake->sent)['body']['text'])->toContain('не горит свет');
    expect(end($fake->sent)['body']['keyboard'][0]['payload']['buttons'][0][0]['type'])->toBe('callback');
});

it('grants the dispatcher role by access code', function () {
    $this->seed(DatabaseSeeder::class);
    $fake = new FakeMaxClient;
    $this->app->instance(MaxClient::class, $fake);

    (new ProcessMaxUpdate(messageUpdate(777, '/dispatcher TEST-CODE')))->handle(app(UpdateDispatcher::class));

    $this->assertDatabaseHas('users', ['max_user_id' => 777, 'role' => 'dispatcher']);
    expect(end($fake->sent)['body']['text'])->toContain('ТСЖ «Демо»');
});
