<?php

declare(strict_types=1);

use App\Bot\Models\OutboxMessage;
use App\Bot\Models\ProcessedUpdate;
use App\Bot\Outbox\OutboxStatus;
use App\Bot\Outbox\OutboxTarget;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use App\Jobs\SendOutboxMessage;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

it('confirms done requests automatically after 72 hours', function () {
    $this->seed(DatabaseSeeder::class);
    $done = ServiceRequest::query()->where('status', RequestStatus::Done->value)->firstOrFail();
    $done->forceFill(['done_at' => CarbonImmutable::now()->subHours(73)])->save();

    $this->artisan('requests:auto-confirm')->assertSuccessful();

    $done->refresh();
    expect($done->status)->toBe(RequestStatus::Confirmed)
        ->and($done->confirmed_by)->toBe(ConfirmedBy::Auto)
        ->and($done->closed_at)->not->toBeNull();
});

it('leaves fresh done requests waiting for the resident', function () {
    $this->seed(DatabaseSeeder::class);

    $this->artisan('requests:auto-confirm')->assertSuccessful();

    expect(ServiceRequest::query()->where('status', RequestStatus::Done->value)->count())->toBe(1);
});

it('marks overdue requests once and warns staff who started the bot', function () {
    $this->seed(DatabaseSeeder::class);
    $max = fakeMax();
    $overdue = ServiceRequest::query()->overdue()->firstOrFail();
    $organizationId = (int) $overdue->organization_id;
    $started = User::query()->create(['max_user_id' => 7001, 'name' => 'Диспетчер Один', 'role' => Role::Dispatcher, 'organization_id' => $organizationId, 'bot_started_at' => CarbonImmutable::now()]);
    User::query()->create(['max_user_id' => 7002, 'name' => 'Диспетчер Два', 'role' => Role::Dispatcher, 'organization_id' => $organizationId]);

    $this->artisan('requests:overdue-scan')->expectsOutputToContain('Срок вышел у заявок: 1')->assertSuccessful();

    $reminders = $overdue->events()->where('type', EventType::Reminder->value)->get();
    expect($reminders)->toHaveCount(1)
        ->and($reminders->first()->payload['kind'])->toBe('overdue')
        ->and($max->sent)->toHaveCount(1)
        ->and($max->sent[0]['id'])->toBe(7001)
        ->and(lastText($max))->toBe(botText('request.overdue_staff', ['number' => $overdue->id, 'what' => $overdue->category->name, 'address' => $overdue->house->address.', подъезд 3']))
        ->and(lastButtons($max))->toBe([(string) config('max.miniapp_url')])
        ->and(OutboxMessage::query()->where('kind', 'request.overdue_staff')->where('target_id', $started->max_user_id)->count())->toBe(1);

    $this->artisan('requests:overdue-scan')->expectsOutputToContain('Срок вышел у заявок: 0')->assertSuccessful();
    expect($overdue->events()->where('type', EventType::Reminder->value)->count())->toBe(1)
        ->and($max->sent)->toHaveCount(1)
        ->and(ServiceRequest::query()->whereHas('events', fn ($q) => $q->where('type', EventType::Reminder->value))->count())->toBe(1);
});

it('requeues outbox messages stuck in pending', function () {
    Queue::fake();
    $stale = OutboxMessage::query()->create(['target_type' => OutboxTarget::User, 'target_id' => 1, 'kind' => 'bot.raw', 'body' => ['text' => 'a'], 'status' => OutboxStatus::Pending]);
    OutboxMessage::query()->whereKey($stale->id)->update([
        'available_at' => CarbonImmutable::now()->subMinutes(20),
        'updated_at' => CarbonImmutable::now()->subMinutes(20),
    ]);
    OutboxMessage::query()->create(['target_type' => OutboxTarget::User, 'target_id' => 2, 'kind' => 'bot.raw', 'body' => ['text' => 'b'], 'status' => OutboxStatus::Pending]);

    $this->artisan('outbox:requeue-stale')->assertSuccessful();

    Queue::assertPushed(SendOutboxMessage::class, fn (SendOutboxMessage $job) => $job->outboxId === $stale->id);
    Queue::assertPushed(SendOutboxMessage::class, 1);
});

it('prunes old update keys', function () {
    ProcessedUpdate::query()->insert([
        ['update_key' => 'old', 'received_at' => CarbonImmutable::now()->subDays(8)],
        ['update_key' => 'fresh', 'received_at' => CarbonImmutable::now()],
    ]);

    $this->artisan('updates:prune')->assertSuccessful();

    expect(ProcessedUpdate::query()->pluck('update_key')->all())->toBe(['fresh']);
});

it('calls pg_dump for the backup', function () {
    Process::fake();

    $this->artisan('db:backup')->assertSuccessful();

    Process::assertRan(fn ($process) => str_contains((string) $process->command, 'pg_dump'));
});
