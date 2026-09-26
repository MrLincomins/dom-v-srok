<?php

declare(strict_types=1);

use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\OutboxStatus;
use App\Jobs\ProcessMaxUpdate;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Queue\Queue as QueueBase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

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
    Queue::assertPushedOn(ProcessMaxUpdate::QUEUE, ProcessMaxUpdate::class);
    $this->assertDatabaseCount('processed_updates', 1);
});

it('forgets the update key when the queue rejects the job, so MAX can retry', function () {
    $headers = ['X-Max-Bot-Api-Secret' => 'test-webhook-secret'];
    QueueBase::createPayloadUsing(fn () => throw new RuntimeException('redis down'));

    try {
        $this->postJson('/max/webhook', messageUpdate(1, 'привет'), $headers)
            ->assertStatus(500)
            ->assertJsonPath('error.code', 'server_error');
    } finally {
        QueueBase::createPayloadUsing(null);
    }

    $this->assertDatabaseCount('processed_updates', 0);
});

it('replies to /start with the greeting and menu, and suggests a category for free text', function () {
    $this->seed(DatabaseSeeder::class);
    $fake = fakeMax();

    runUpdate(startUpdate(555, 'h_'.DemoSeeder::HOUSE_QR_TOKEN.'_2'));

    $this->assertDatabaseHas('users', ['max_user_id' => 555, 'entrance' => 2]);
    expect($fake->texts())->toContain(botText('start.greeting'));
    expect(implode("\n", $fake->texts()))->toContain(DemoSeeder::HOUSE_ADDRESS);

    runUpdate(messageUpdate(555, 'не горит свет', 'mid.2'));
    expect(lastText($fake))->toBe(botText('report.suggest'));
    expect(lastButtons($fake)[0] ?? '')->toStartWith('sub:');
});

it('grants the dispatcher role by access code', function () {
    $this->seed(DatabaseSeeder::class);
    $fake = fakeMax();

    runUpdate(messageUpdate(777, '/dispatcher TEST-CODE'));

    $this->assertDatabaseHas('users', ['max_user_id' => 777, 'role' => 'dispatcher']);
    expect(lastText($fake))->toContain('ТСЖ «Демо»');
});

it('answers a button press to the person who pressed it, not to the bot', function () {
    $this->seed(DatabaseSeeder::class);
    $fake = fakeMax();

    runUpdate(messageUpdate(888, '/start'));
    runUpdate(callbackUpdate(888, 'my', 'cb.1'));

    $row = OutboxMessage::query()->latest('id')->firstOrFail();
    expect($fake->answered)->toContain('cb.1')
        ->and(lastAnswer($fake)['message']['text'] ?? '')->toContain(botText('my.empty'))
        ->and($row->target_id)->toBe(888)
        ->and($row->status)->toBe(OutboxStatus::Sent)
        ->and($row->max_message_id)->toBe('mid.bot.cb.1');
});

it('does not repeat replies when the same update is processed twice', function () {
    $this->seed(DatabaseSeeder::class);
    $fake = fakeMax();

    runUpdate(startUpdate(999));
    $count = OutboxMessage::query()->count();
    runUpdate(startUpdate(999));

    expect(OutboxMessage::query()->count())->toBe($count)
        ->and(count($fake->sent))->toBe($count);
});

it('does not treat a button press on a deleted message as a started dialog', function () {
    $this->seed(DatabaseSeeder::class);
    fakeMax();
    $update = callbackUpdate(444, 'menu', 'cb.gone');
    $update['message'] = null;

    runUpdate($update);

    $this->assertDatabaseHas('users', ['max_user_id' => 444, 'bot_started_at' => null]);
});

it('logs how long an update travelled and how long it was handled', function () {
    $this->seed(DatabaseSeeder::class);
    fakeMax();
    Log::spy();

    runUpdate(startUpdate(1001));

    Log::shouldHaveReceived('info')->withArgs(fn (string $event, array $context = []) => $event === 'bot.latency'
        && array_key_exists('delivery_ms', $context)
        && array_key_exists('queue_ms', $context)
        && $context['handling_ms'] >= 0)->once();
});
