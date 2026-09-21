<?php

declare(strict_types=1);

use App\Domain\Organizations\Models\Organization;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Spectator\Spectator;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
    $this->resident = $this->postJson('/api/v1/auth/login', ['login' => 'demo_resident', 'password' => 'resident-pass'])->json('data.token');
});

it('shows the organisation card to any signed-in user by id', function () {
    $organization = Organization::query()->where('is_demo', true)->firstOrFail();

    asToken($this->resident)->getJson("/api/v1/organizations/{$organization->id}")
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('data.name', DemoSeeder::ORGANIZATION_NAME)
        ->assertJsonPath('data.phone_ads', '+7 843 000-00-01')
        ->assertJsonMissingPath('data.staff');

    asToken($this->resident)->getJson('/api/v1/organizations/'.($organization->id + 100))
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'not_found');
    app('auth')->forgetGuards();
    $this->withoutToken()->getJson("/api/v1/organizations/{$organization->id}")->assertStatus(401);
});
