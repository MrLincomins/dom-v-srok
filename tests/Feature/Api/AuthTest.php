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

it('accepts initData where MAX encodes spaces as plus signs', function () {
    $initData = InitDataValidator::sign([
        'auth_date' => (string) time(),
        'chat' => '{"id":1,"type":"DIALOG"}',
        'ip' => '127.0.0.1',
        'user' => json_encode(['id' => 515151, 'first_name' => 'Анна Мария', 'last_name' => 'Иванова Петрова'], JSON_UNESCAPED_UNICODE),
    ], 'test-bot-token');

    expect($initData)->toContain('+')->not->toContain('%20');

    $this->postJson('/api/v1/auth/max', ['init_data' => $initData])
        ->assertOk()
        ->assertJsonPath('data.user.name', 'Анна Мария Иванова Петрова');
});

it('accepts initData with percent-encoded spaces as well', function () {
    $params = ['auth_date' => (string) time(), 'user' => '{"id":616161,"first_name":"Пётр Первый"}'];
    $signed = InitDataValidator::sign($params, 'test-bot-token');
    $initData = str_replace('+', '%20', $signed);

    $this->postJson('/api/v1/auth/max', ['init_data' => $initData])->assertOk();
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

it('explains validation errors in Russian', function () {
    $this->postJson('/api/v1/auth/login', ['password' => 'x'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.details.fields.login.0', 'Поле «логин» обязательно.');
});

it('answers 429 with Retry-After when the login is hammered', function () {
    foreach (range(1, 30) as $attempt) {
        $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'nope'])->assertStatus(401);
    }

    $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'nope'])
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'too_many_requests')
        ->assertHeader('Retry-After')
        ->assertHeader('X-RateLimit-Limit', '30')
        ->assertHeader('X-RateLimit-Remaining', '0');
});

it('answers an unsupported method in Russian', function () {
    $this->getJson('/api/v1/auth/login')
        ->assertStatus(405)
        ->assertJsonPath('error.code', 'http_error')
        ->assertJsonPath('error.message', 'Метод не поддерживается');
});

it('rejects initData signed with auth_date from the future', function () {
    $sign = fn (int $authDate): string => InitDataValidator::sign([
        'auth_date' => (string) $authDate,
        'user' => '{"id":717171,"first_name":"Олег"}',
    ], 'test-bot-token');

    $this->postJson('/api/v1/auth/max', ['init_data' => $sign(now()->addMinutes(5)->getTimestamp())])
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'unauthenticated');
    $this->postJson('/api/v1/auth/max', ['init_data' => $sign(now()->addSeconds(30)->getTimestamp())])
        ->assertOk();
});

it('lets the mini app sign in again with the same initData', function () {
    $initData = InitDataValidator::sign(['auth_date' => (string) now()->getTimestamp(), 'user' => '{"id":727272,"first_name":"Олег"}'], 'test-bot-token');

    $this->postJson('/api/v1/auth/max', ['init_data' => $initData])->assertOk();
    $this->postJson('/api/v1/auth/max', ['init_data' => $initData])->assertOk();
});
