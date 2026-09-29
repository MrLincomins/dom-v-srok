<?php

declare(strict_types=1);

use App\Bot\Fsm\DialogState;
use App\Bot\Models\BotSession;
use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Demo\DemoResetService;
use App\Domain\Organizations\Enums\ContractorType;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Models\EmergencySignal;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->organization = Organization::query()->where('is_demo', true)->firstOrFail();
    $this->house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
});

it('returns everything the reviewer could change to the seeded state', function () {
    $initial = $this->organization->only(['direct_cold_water', 'direct_hot_water', 'direct_heat', 'direct_power', 'direct_tko']);
    $this->organization->update(array_map(fn (bool $flag) => ! $flag, $initial));
    $this->organization->contractors()->create(['type' => ContractorType::Lift, 'name' => 'ООО «Лифт-Сервис»']);
    $this->organization->executors()->create(['name' => 'Новый мастер', 'is_active' => true]);
    $this->organization->executors()->where('name', DemoSeeder::EXECUTORS[0]['name'])->update(['is_active' => false]);
    $this->house->update(['chat_keywords_enabled' => true, 'entrances' => 7, 'max_chat_id' => 555]);
    $resident = User::query()->create(['max_user_id' => 9101, 'name' => 'Житель', 'role' => Role::Resident, 'house_id' => $this->house->id]);
    BotSession::query()->create(['max_user_id' => 9101, 'state' => DialogState::cases()[1], 'payload' => ['repeat_of_id' => 3]]);
    EmergencySignal::query()->create(['user_id' => $resident->id, 'house_id' => $this->house->id, 'organization_id' => $this->organization->id, 'phone_shown' => '+7 843 000-00-01']);

    app(DemoResetService::class)->reset();

    $this->organization->refresh();
    $this->house->refresh();
    expect($this->organization->only(array_keys($initial)))->toBe($initial)
        ->and($initial)->toBe(['direct_cold_water' => true, 'direct_hot_water' => false, 'direct_heat' => false, 'direct_power' => false, 'direct_tko' => false])
        ->and($this->organization->contractors()->pluck('name')->all())->toBe(array_column(DemoSeeder::CONTRACTORS, 'name'))
        ->and($this->organization->executors()->orderBy('id')->get()->map->only(['name', 'is_active'])->all())
        ->toBe(array_map(fn (array $e) => ['name' => $e['name'], 'is_active' => true], DemoSeeder::EXECUTORS))
        ->and($this->house->chat_keywords_enabled)->toBeFalse()
        ->and($this->house->entrances)->toBe(3)
        ->and($this->house->max_chat_id)->toBe(555)
        ->and(BotSession::query()->count())->toBe(0)
        ->and(EmergencySignal::query()->count())->toBe(0);
});

it('numbers the demo requests from one again and is idempotent', function () {
    app(DemoResetService::class)->reset();
    app(DemoResetService::class)->reset();

    expect(ServiceRequest::query()->orderBy('id')->pluck('id')->all())->toBe(range(1, 8));
});

it('keeps the event feed of the overdue request no younger than the request', function () {
    $overdue = ServiceRequest::query()->overdue()->with('events')->sole();
    $created = $overdue->events->firstWhere('type', EventType::Created);

    expect($overdue->created_at->lessThan(CarbonImmutable::now()->subDays(2)))->toBeTrue()
        ->and($created->created_at->equalTo($overdue->created_at))->toBeTrue()
        ->and($overdue->deadline_fix_at?->lessThan(CarbonImmutable::now()))->toBeTrue()
        ->and($overdue->deadline_reply_at?->toIso8601String())
        ->toBe(app(DeadlineCalculator::class)->replyDeadline($overdue->category, $overdue->created_at, 'Europe/Moscow')?->toIso8601String());
});
