<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->dispatcher = $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])->json('data.token');
});

it('allows only two demo resets a minute per user', function () {
    asToken($this->dispatcher)->postJson('/api/v1/demo/reset')->assertOk();
    asToken($this->dispatcher)->postJson('/api/v1/demo/reset')->assertOk();

    asToken($this->dispatcher)->postJson('/api/v1/demo/reset')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'too_many_requests');
});
