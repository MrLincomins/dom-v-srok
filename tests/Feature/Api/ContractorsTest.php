<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Enums\RequestOrigin;
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

it('manages contractors and routes new requests of their type to them', function () {
    asToken($this->dispatcher)->getJson('/api/v1/organization/contractors')
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'intercom')
        ->assertJsonPath('data.0.type_label', 'Домофонная компания');

    $id = asToken($this->dispatcher)->postJson('/api/v1/organization/contractors', ['type' => 'lift', 'name' => 'ООО «Лифт-Сервис»', 'phone' => '+7 843 000-00-05'])
        ->assertValidRequest()->assertValidResponse(201)
        ->assertJsonPath('data.name', 'ООО «Лифт-Сервис»')
        ->json('data.id');

    $house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
    $resident = User::query()->where('login', 'demo_resident')->firstOrFail();
    $request = app(RequestService::class)->create(new CreateRequestData(
        houseId: $house->id,
        residentUserId: $resident->id,
        categoryId: (int) Category::query()->where('slug', 'lift.noise')->value('id'),
        description: 'Лифт скрипит',
        entrance: 1,
        origin: RequestOrigin::Direct,
    ));
    expect($request->responsible_name)->toContain('ООО «Лифт-Сервис»')
        ->and($request->responsible_phone)->toBe('+7 843 000-00-05')
        ->and($request->is_sure)->toBeTrue();

    asToken($this->dispatcher)->postJson('/api/v1/organization/contractors', ['type' => 'lift', 'name' => 'ООО «Лифт-Сервис»'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
    asToken($this->dispatcher)->postJson('/api/v1/organization/contractors', ['type' => 'gas', 'name' => 'Газ'])
        ->assertStatus(422);

    asToken($this->dispatcher)->deleteJson("/api/v1/organization/contractors/{$id}")
        ->assertValidResponse(200)
        ->assertJsonPath('data.ok', true);
    asToken($this->dispatcher)->getJson('/api/v1/organization/contractors')->assertJsonCount(1, 'data');
    asToken($this->dispatcher)->deleteJson("/api/v1/organization/contractors/{$id}")->assertStatus(404);
    asToken($this->resident)->getJson('/api/v1/organization/contractors')->assertStatus(403);
});
