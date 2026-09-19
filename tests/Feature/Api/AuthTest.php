<?php

declare(strict_types=1);

use App\Support\Auth\InitDataValidator;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('logs a demo dispatcher in with login and password', function () {
    $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])
        ->assertOk()
        ->assertJsonPath('data.user.role', 'dispatcher')
        ->assertJsonStructure(['data' => ['token', 'expires_at', 'user' => ['id', 'name', 'role', 'organization']]]);
});

it('rejects a wrong password with the unified error format', function () {
    $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'nope'])
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('validates initData signed with the bot token and creates the user', function () {
    $initData = InitDataValidator::sign([
        'query_id' => 'q-1',
        'auth_date' => (string) time(),
        'user' => json_encode(['id' => 424242, 'first_name' => 'Иван', 'last_name' => 'Тестов', 'username' => 'ivan'], JSON_UNESCAPED_UNICODE),
    ], 'test-bot-token');

    $this->postJson('/api/v1/auth/max', ['init_data' => $initData])
        ->assertOk()
        ->assertJsonPath('data.user.name', 'Иван Тестов')
        ->assertJsonPath('data.user.role', 'resident');

    $this->assertDatabaseHas('users', ['max_user_id' => 424242, 'role' => 'resident']);
});

it('rejects tampered initData', function () {
    $initData = InitDataValidator::sign(['auth_date' => (string) time(), 'user' => '{"id":1}'], 'other-token');

    $this->postJson('/api/v1/auth/max', ['init_data' => $initData])->assertStatus(401);
});

it('answers 401 in the unified format even without an Accept header', function () {
    $this->get('/api/v1/me')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('returns the profile for a bearer token', function () {
    $token = $this->postJson('/api/v1/auth/login', ['login' => 'demo_resident', 'password' => 'resident-pass'])->json('data.token');

    $this->withToken($token)->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.role', 'resident')
        ->assertJsonPath('data.house.address', DemoSeeder::HOUSE_ADDRESS);
});
