<?php

declare(strict_types=1);

use App\Bot\Cards\RequestCard;
use App\Bot\Models\OutboxMessage;
use App\Bot\Models\ProcessedUpdate;
use App\Bot\Outbox\OutboxStatus;
use App\Bot\Outbox\OutboxTarget;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use App\Jobs\SendOutboxMessage;
use App\Support\Models\AppSetting;
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
        ->and(lastButtons($max))->toBe([(string) config('max.bot_username')])
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

it('sets the bot commands from the texts table', function () {
    $this->seed(DatabaseSeeder::class);
    $fake = fakeMax();

    $this->artisan('bot:commands')->assertSuccessful();

    expect(array_column($fake->commands, 'name'))->toBe(['start', 'menu', 'dispatcher', 'delete_me'])
        ->and($fake->commands[1]['description'])->toBe(botText('command.menu'))
        ->and(AppSetting::get('bot')['id'])->toBe(405671160);
});

it('marks the demo as seeded only when it was created', function () {
    config(['demo.seed' => false]);

    $this->artisan('demo:seed-once')->assertSuccessful();

    expect(AppSetting::get('seeded_at'))->toBeNull()
        ->and(Organization::query()->where('is_demo', true)->exists())->toBeFalse();

    config(['demo.seed' => true]);

    $this->artisan('demo:seed-once')->assertSuccessful();
    $this->artisan('demo:seed-once')->expectsOutputToContain('Демо уже создано')->assertSuccessful();

    expect(AppSetting::get('seeded_at'))->not->toBeNull()
        ->and(Organization::query()->where('is_demo', true)->count())->toBe(1)
        ->and(ServiceRequest::query()->count())->toBe(8);
});

it('warns staff once when a deadline is less than two hours away', function () {
    $this->seed(DatabaseSeeder::class);
    $max = fakeMax();
    $overdue = ServiceRequest::query()->overdue()->firstOrFail();
    $open = ServiceRequest::query()->open()->whereKeyNot($overdue->id)->orderBy('id')->get();
    $soon = $open->first();
    $later = $open->last();
    ServiceRequest::query()->whereIn('id', $open->pluck('id'))->update(['deadline_fix_at' => CarbonImmutable::now()->addHours(5)]);
    $soon->forceFill(['deadline_fix_at' => CarbonImmutable::now()->addHour()])->save();
    $organizationId = (int) $soon->organization_id;
    User::query()->create(['max_user_id' => 7001, 'name' => 'Диспетчер Один', 'role' => Role::Dispatcher, 'organization_id' => $organizationId, 'bot_started_at' => CarbonImmutable::now()]);
    User::query()->create(['max_user_id' => 7002, 'name' => 'Диспетчер Два', 'role' => Role::Dispatcher, 'organization_id' => $organizationId]);

    $this->artisan('requests:due-soon')->expectsOutputToContain('Срок скоро выйдет у заявок: 1')->assertSuccessful();

    $soon->refresh()->load(['category', 'house.region']);
    $reminders = $soon->events()->where('type', EventType::Reminder->value)->get();
    expect($reminders)->toHaveCount(1)
        ->and($reminders->first()->payload['kind'])->toBe('due_soon')
        ->and($max->sent)->toHaveCount(1)
        ->and($max->sent[0]['id'])->toBe(7001)
        ->and(lastText($max))->toBe(botText('request.due_soon_staff', [
            'number' => $soon->id,
            'what' => $soon->category->name,
            'address' => $soon->house->address.($soon->entrance !== null ? ', подъезд '.$soon->entrance : ''),
            'deadline' => app(RequestCard::class)->deadline($soon),
        ]))
        ->and(OutboxMessage::query()->where('kind', 'request.due_soon_staff')->where('request_id', $soon->id)->count())->toBe(1);

    $this->artisan('requests:due-soon')->expectsOutputToContain('Срок скоро выйдет у заявок: 0')->assertSuccessful();

    expect($max->sent)->toHaveCount(1)
        ->and(OutboxMessage::query()->where('kind', 'request.due_soon_staff')->count())->toBe(1)
        ->and($later->events()->where('type', EventType::Reminder->value)->exists())->toBeFalse()
        ->and($overdue->events()->where('type', EventType::Reminder->value)->exists())->toBeFalse();
});

it('sends the morning digest once a day to staff who started the bot', function () {
    $this->seed(DatabaseSeeder::class);
    $max = fakeMax();
    $organization = Organization::query()->where('is_demo', true)->firstOrFail();
    $started = User::query()->create(['max_user_id' => 7001, 'name' => 'Диспетчер Один', 'role' => Role::Dispatcher, 'organization_id' => $organization->id, 'bot_started_at' => CarbonImmutable::now()]);
    User::query()->create(['max_user_id' => 7002, 'name' => 'Диспетчер Два', 'role' => Role::Dispatcher, 'organization_id' => $organization->id]);
    $card = app(RequestCard::class);
    $top = ServiceRequest::query()->with(['category', 'house.region'])->forOrganization($organization->id)->open()
        ->orderByRaw('deadline_fix_at ASC NULLS LAST')->orderBy('id')->limit(3)->get();
    $overdue = ServiceRequest::query()->overdue()->firstOrFail();

    $this->artisan('requests:morning-digest')->expectsOutputToContain('Сводка для организаций: 1')->assertSuccessful();

    $list = $top->map(fn (ServiceRequest $r) => '№ '.$r->id.': '.$r->category->name.', '.$card->deadline($r))->implode('; ');
    expect($top)->toHaveCount(3)
        ->and($top->first()->id)->toBe($overdue->id)
        ->and($max->sent)->toHaveCount(1)
        ->and($max->sent[0]['id'])->toBe(7001)
        ->and(lastText($max))->toBe(botText('digest.morning', [
            'new' => 3,
            'active' => 3,
            'overdue' => 1,
            'waiting' => 1,
            'top' => botText('digest.morning_top', ['list' => $list]),
        ]))
        ->and(lastButtons($max))->toBe([(string) config('max.bot_username')]);

    $this->artisan('requests:morning-digest')->assertSuccessful();

    expect($max->sent)->toHaveCount(1)
        ->and(OutboxMessage::query()->where('kind', 'digest.morning')->where('target_id', $started->max_user_id)->count())->toBe(1);
});

it('skips the morning digest for organizations without staff in the bot', function () {
    $this->seed(DatabaseSeeder::class);
    $max = fakeMax();
    $organization = Organization::query()->where('is_demo', true)->firstOrFail();
    User::query()->create(['max_user_id' => 7002, 'name' => 'Диспетчер Два', 'role' => Role::Dispatcher, 'organization_id' => $organization->id]);

    $this->artisan('requests:morning-digest')->expectsOutputToContain('Сводка для организаций: 0')->assertSuccessful();

    expect($max->sent)->toHaveCount(0)
        ->and(OutboxMessage::query()->where('kind', 'digest.morning')->count())->toBe(0);
});
