<?php

declare(strict_types=1);

use App\Domain\Organizations\AccessCodeService;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

it('leaves the demo accounts without a password when the environment has none', function () {
    config(['demo.dispatcher_password' => '', 'demo.resident_password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('login', 'demo_dispatcher')->value('password'))->toBeNull()
        ->and(User::query()->where('login', 'demo_resident')->value('password'))->toBeNull();

    $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'change-me'])
        ->assertStatus(401);
});

it('revokes the previous access code when the demo code changes', function () {
    $this->seed(DatabaseSeeder::class);
    $codes = app(AccessCodeService::class);
    $user = fn (int $id): User => User::query()->create(['max_user_id' => $id, 'name' => 'Проверяющий', 'role' => Role::Resident]);

    config(['demo.access_code' => 'NEW-CODE']);
    (new DemoSeeder)->run();

    expect($codes->redeem($user(8101), 'TEST-CODE'))->toBeNull()
        ->and($codes->redeem($user(8102), 'NEW-CODE'))->not->toBeNull();

    config(['demo.access_code' => '']);
    (new DemoSeeder)->run();

    expect($codes->redeem($user(8103), 'NEW-CODE'))->toBeNull();
});
