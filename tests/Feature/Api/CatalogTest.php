<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Spectator\Spectator;

function catalogCategory(string $slug): int
{
    return (int) Category::query()->where('slug', $slug)->value('id');
}

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
    $this->dispatcher = $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])->json('data.token');
    $this->resident = $this->postJson('/api/v1/auth/login', ['login' => 'demo_resident', 'password' => 'resident-pass'])->json('data.token');
    $this->house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
    $this->foreign = House::query()->create(['region_code' => 'RU-TA', 'address' => 'Казань, ул. Чужая, д. 2']);
});

it('tells a resident who is responsible for their own house', function () {
    asToken($this->resident)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('entrance.light'))
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('data.category.id', catalogCategory('entrance.light'))
        ->assertJsonPath('data.category.is_emergency', false)
        ->assertJsonPath('data.house_id', $this->house->id)
        ->assertJsonPath('data.responsible.kind', 'organization')
        ->assertJsonPath('data.responsible.name', DemoSeeder::ORGANIZATION_NAME)
        ->assertJsonPath('data.responsible.is_sure', true)
        ->assertJson(fn ($json) => $json->whereType('data.deadline_fix_at', 'string')->etc());

    asToken($this->resident)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('entrance.light').'&house_id='.$this->foreign->id)
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'not_found');
});

it('lets a dispatcher look up any house of their organisation only', function () {
    asToken($this->dispatcher)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('entrance.light').'&house_id='.$this->house->id)
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('data.responsible.name', DemoSeeder::ORGANIZATION_NAME);

    asToken($this->dispatcher)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('entrance.light').'&house_id='.$this->foreign->id)
        ->assertStatus(404);
    asToken($this->dispatcher)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('entrance.light'))
        ->assertValidResponse(422)
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['details' => ['fields' => ['house_id']]]]);
});

it('gives no deadlines for an emergency and sends to the emergency line', function () {
    asToken($this->resident)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('emergency.pipe'))
        ->assertValidResponse(200)
        ->assertJsonPath('data.category.is_emergency', true)
        ->assertJsonPath('data.deadline_fix_at', null)
        ->assertJsonPath('data.deadline_reply_at', null)
        ->assertJsonPath('data.responsible.phone', '+7 843 000-00-01');
});

it('rejects a resident without a house, a missing category and a guest', function () {
    User::query()->where('login', 'demo_resident')->update(['house_id' => null]);

    asToken($this->resident)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('entrance.light'))
        ->assertValidResponse(422)
        ->assertJsonStructure(['error' => ['details' => ['fields' => ['house_id']]]]);
    asToken($this->resident)->getJson('/api/v1/catalog/responsible?category_id='.catalogCategory('entrance').'&house_id='.$this->house->id)
        ->assertStatus(404);
    asToken($this->resident)->getJson('/api/v1/catalog/responsible')
        ->assertStatus(422)
        ->assertJsonStructure(['error' => ['details' => ['fields' => ['category_id']]]]);

    app('auth')->forgetGuards();
    $this->withoutToken()->getJson('/api/v1/catalog/responsible?category_id=1')->assertStatus(401);
});
