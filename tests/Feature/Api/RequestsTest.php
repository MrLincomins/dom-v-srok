<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;
use Spectator\Spectator;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
    $this->dispatcher = $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])->json('data.token');
    $this->resident = $this->postJson('/api/v1/auth/login', ['login' => 'demo_resident', 'password' => 'resident-pass'])->json('data.token');
});

it('lists the queue with counters and matches the contract', function () {
    $response = asToken($this->dispatcher)->getJson('/api/v1/requests?status=open');

    $response->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('meta.counters.overdue', 1)
        ->assertJsonPath('meta.counters.closed', 2);

    expect($response->json('data'))->toHaveCount(5);
});

it('shows only overdue requests when asked', function () {
    asToken($this->dispatcher)->getJson('/api/v1/requests?overdue=1')
        ->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.is_overdue', true);
});

it('forbids the queue for a resident', function () {
    asToken($this->resident)->getJson('/api/v1/requests')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'forbidden');
});

it('returns a request card that matches the contract', function () {
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->getJson("/api/v1/requests/{$id}")
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'new')
        ->assertJsonStructure(['data' => ['responsible' => ['name', 'is_sure'], 'events', 'allowed_transitions']]);
});

it('moves a request through the workflow and rejects a bad transition with 409', function () {
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->patchJson("/api/v1/requests/{$id}/status", ['status' => 'in_progress', 'comment' => 'Выехал'])
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'in_progress');

    asToken($this->dispatcher)->patchJson("/api/v1/requests/{$id}/status", ['status' => 'new'])
        ->assertValidResponse(409)
        ->assertJsonPath('error.code', 'invalid_transition');
});

it('lets the resident confirm a done request and return another one to work', function () {
    $done = asToken($this->dispatcher)->getJson('/api/v1/requests?status=done')->json('data.0.id');

    asToken($this->resident)->postJson("/api/v1/requests/{$done}/confirm", ['resolved' => false, 'comment' => 'Дверь снова не закрывается'])
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'returned')
        ->assertJsonPath('data.returned_count', 1);

    asToken($this->dispatcher)->patchJson("/api/v1/requests/{$done}/status", ['status' => 'done'])->assertOk();
    asToken($this->resident)->postJson("/api/v1/requests/{$done}/confirm", ['resolved' => true])
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.confirmed_by', 'resident');
});

it('redirects a request with a contact', function () {
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->postJson("/api/v1/requests/{$id}/redirect", ['name' => 'Водоканал', 'phone' => '+7 843 000-00-00', 'note' => 'Магистраль'])
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'redirected')
        ->assertJsonPath('data.redirected_to.note', 'Магистраль');
});

it('resets the demo organisation on request', function () {
    asToken($this->dispatcher)->postJson('/api/v1/demo/reset')->assertOk();
    asToken($this->dispatcher)->getJson('/api/v1/requests')->assertOk()->assertJsonPath('meta.total', 8);
});
