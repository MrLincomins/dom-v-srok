<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Enums\RequestOrigin;
use App\Domain\Requests\Enums\ResponsibleKind;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Spectator\Spectator;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
    $this->dispatcher = $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])->json('data.token');
    $this->resident = $this->postJson('/api/v1/auth/login', ['login' => 'demo_resident', 'password' => 'resident-pass'])->json('data.token');
});

it('shows the organisation card to staff only', function () {
    asToken($this->dispatcher)->getJson('/api/v1/organization')
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('data.name', DemoSeeder::ORGANIZATION_NAME)
        ->assertJsonPath('data.direct_contracts.tko', true)
        ->assertJsonPath('data.direct_contracts.cold_water', false);

    asToken($this->resident)->getJson('/api/v1/organization')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'forbidden');
});

it('updates contacts and direct contract flags, and new requests follow the flag', function () {
    asToken($this->dispatcher)->patchJson('/api/v1/organization', [
        'phone_dispatch' => '+7 843 000-00-09',
        'direct_contracts' => ['cold_water' => true],
    ])
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('data.phone_dispatch', '+7 843 000-00-09')
        ->assertJsonPath('data.direct_contracts.cold_water', true)
        ->assertJsonPath('data.direct_contracts.tko', true)
        ->assertJsonPath('data.name', DemoSeeder::ORGANIZATION_NAME);

    $house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
    $resident = User::query()->where('login', 'demo_resident')->firstOrFail();
    $request = app(RequestService::class)->create(new CreateRequestData(
        houseId: $house->id,
        residentUserId: $resident->id,
        categoryId: (int) Category::query()->where('slug', 'water.cold_none')->value('id'),
        description: 'Нет воды во всём доме',
        entrance: 2,
        flat: '45',
        origin: RequestOrigin::Direct,
    ));

    expect($request->responsible_kind)->toBe(ResponsibleKind::Party)
        ->and($request->responsible_name)->toContain('Водоканал');
});

it('rejects an empty emergency phone and unknown contract keys', function () {
    asToken($this->dispatcher)->patchJson('/api/v1/organization', ['phone_ads' => ''])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');

    asToken($this->dispatcher)->patchJson('/api/v1/organization', ['direct_contracts' => ['gas' => true]])
        ->assertStatus(422);
});

it('lists houses with the QR link and toggles keyword hints in the chat', function () {
    $response = asToken($this->dispatcher)->getJson('/api/v1/organization/houses')
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.qr_token', DemoSeeder::HOUSE_QR_TOKEN)
        ->assertJsonPath('data.0.chat_bound', false)
        ->assertJsonPath('data.0.chat_keywords_enabled', false);
    expect($response->json('data.0.start_url'))->toContain('start=h_'.DemoSeeder::HOUSE_QR_TOKEN);
    $id = $response->json('data.0.id');

    asToken($this->dispatcher)->patchJson("/api/v1/organization/houses/{$id}", ['chat_keywords_enabled' => true, 'entrances' => 4])
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('data.chat_keywords_enabled', true)
        ->assertJsonPath('data.entrances', 4);

    $foreign = House::query()->create(['region_code' => 'RU-TA', 'address' => 'Казань, ул. Чужая, д. 2']);
    asToken($this->dispatcher)->patchJson("/api/v1/organization/houses/{$foreign->id}", ['entrances' => 2])
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'not_found');
});

it('manages executors: list, add, edit, archive; archived ones cannot be assigned', function () {
    asToken($this->dispatcher)->getJson('/api/v1/organization/executors')
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Сантехник Иванов');

    $id = asToken($this->dispatcher)->postJson('/api/v1/organization/executors', ['name' => 'Кровельщик Сидоров', 'specialty' => 'кровельщик'])
        ->assertValidRequest()->assertValidResponse(201)
        ->assertJsonPath('data.name', 'Кровельщик Сидоров')
        ->json('data.id');

    asToken($this->dispatcher)->patchJson("/api/v1/organization/executors/{$id}", ['phone' => '+7 900 000-00-13'])
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('data.phone', '+7 900 000-00-13')
        ->assertJsonPath('data.specialty', 'кровельщик');

    asToken($this->dispatcher)->deleteJson("/api/v1/organization/executors/{$id}")
        ->assertValidResponse(200)
        ->assertJsonPath('data.ok', true);
    asToken($this->dispatcher)->getJson('/api/v1/organization/executors')->assertJsonCount(2, 'data');

    $request = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');
    asToken($this->dispatcher)->postJson("/api/v1/requests/{$request}/assign", ['executor_id' => $id])
        ->assertStatus(404);

    asToken($this->dispatcher)->postJson('/api/v1/organization/executors', ['specialty' => 'без имени'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
    asToken($this->resident)->getJson('/api/v1/organization/executors')->assertStatus(403);
});
