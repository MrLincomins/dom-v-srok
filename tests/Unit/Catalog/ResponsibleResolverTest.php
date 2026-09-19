<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\ResponsibleResolver;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Enums\ResponsibleKind;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function resolverCategory(string $slug): Category
{
    return Category::query()->where('slug', $slug)->firstOrFail();
}

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->with(['organization', 'region'])->firstOrFail();
    $this->resolver = new ResponsibleResolver;
});

it('sends house categories to the organization', function () {
    $r = $this->resolver->resolve(resolverCategory('entrance.light'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->name)->toBe(DemoSeeder::ORGANIZATION_NAME)
        ->and($r->phone)->toBe('+7 843 000-00-02')
        ->and($r->isSure)->toBeTrue();
});

it('keeps a resource category with the organization when there is no direct contract', function () {
    $r = $this->resolver->resolve(resolverCategory('water.cold_none'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->isSure)->toBeFalse()
        ->and($r->hint)->toContain('Водоканал');
});

it('sends a resource category to the supplier when the organization has a direct contract', function () {
    $this->house->organization->update(['direct_cold_water' => true]);

    $r = $this->resolver->resolve(resolverCategory('water.cold_none'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toContain('Водоканал')
        ->and($r->isSure)->toBeFalse()
        ->and($r->partyId)->not->toBeNull();
});

it('prefers the organization contractor for the intercom', function () {
    $r = $this->resolver->resolve(resolverCategory('intercom.broken'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->name)->toContain('Домофон-Сервис')
        ->and($r->phone)->toBe('+7 843 000-00-03')
        ->and($r->isSure)->toBeTrue();
});

it('sends waste removal to the regional operator', function () {
    $r = $this->resolver->resolve(resolverCategory('waste.not_removed'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toContain('ПЖКХ')
        ->and($r->isSure)->toBeTrue();
});

it('sends yard problems to the municipal party', function () {
    $r = $this->resolver->resolve(resolverCategory('other.yard'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toContain('Открытая Казань')
        ->and($r->isSure)->toBeTrue();
});

it('answers an emergency with the organization emergency phone', function () {
    $r = $this->resolver->resolve(resolverCategory('emergency.pipe'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->name)->toBe('АДС '.DemoSeeder::ORGANIZATION_NAME)
        ->and($r->phone)->toBe('+7 843 000-00-01');
});

it('falls back to the regional party or unknown for a house without an organization', function () {
    $house = House::query()->create(['region_code' => 'RU-TA', 'address' => 'Казань, ул. Тестовая, д. 5']);

    $water = $this->resolver->resolve(resolverCategory('water.cold_none'), $house);
    $light = $this->resolver->resolve(resolverCategory('entrance.light'), $house);

    expect($water->kind)->toBe(ResponsibleKind::Party)
        ->and($water->name)->toContain('Водоканал')
        ->and($water->isSure)->toBeTrue()
        ->and($light->kind)->toBe(ResponsibleKind::Unknown)
        ->and($light->isSure)->toBeFalse();
});
