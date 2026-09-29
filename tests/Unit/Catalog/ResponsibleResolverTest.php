<?php

declare(strict_types=1);

use App\Domain\Catalog\Enums\ResponsibleType;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\ResponsibleParty;
use App\Domain\Catalog\ResponsibleResolver;
use App\Domain\Organizations\Enums\ContractorType;
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
    $this->house->organization->update(['direct_cold_water' => false]);

    $r = $this->resolver->resolve(resolverCategory('water.cold_none'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->isSure)->toBeFalse()
        ->and($r->hint)->toContain('Водоканал')
        ->and($r->hint)->toContain('+7 843 231-62-60');
});

it('sends cold water in the demo house to the water utility under the seeded direct contract', function () {
    expect($this->house->organization->direct_cold_water)->toBeTrue();

    $r = $this->resolver->resolve(resolverCategory('water.cold_none'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toBe('МУП «Водоканал» г. Казани')
        ->and($r->phone)->toBe('+7 843 231-62-60')
        ->and($r->isSure)->toBeFalse()
        ->and($r->partyId)->not->toBeNull()
        ->and($r->hint)->toContain('прямой договор')
        ->and($r->hint)->not->toContain('Телефон уточните');
});

it('prefers the organization contractor for the intercom', function () {
    $r = $this->resolver->resolve(resolverCategory('intercom.broken'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->name)->toContain('Домофон-Сервис')
        ->and($r->phone)->toBe('+7 843 000-00-03')
        ->and($r->isSure)->toBeTrue();
});

it('keeps waste removal with the organization when there is no direct contract', function () {
    $r = $this->resolver->resolve(resolverCategory('waste.not_removed'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->name)->toBe(DemoSeeder::ORGANIZATION_NAME)
        ->and($r->isSure)->toBeFalse()
        ->and($r->hint)->toContain('ПЖКХ');
});

it('sends waste removal to the regional operator under a direct contract', function () {
    $this->house->organization->update(['direct_tko' => true]);
    $this->house->organization->contractors()->create(['type' => ContractorType::Tko, 'name' => 'ООО «Вывоз»']);

    $r = $this->resolver->resolve(resolverCategory('waste.not_removed'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toContain('ПЖКХ')
        ->and($r->phone)->toBe('+7 843 260-02-40')
        ->and($r->isSure)->toBeTrue()
        ->and($r->hint)->not->toContain('Телефон уточните');
});

it('prefers the organization waste contractor without a direct contract', function () {
    $this->house->organization->contractors()->create(['type' => ContractorType::Tko, 'name' => 'ООО «Вывоз»', 'phone' => '+7 843 000-00-07']);
    $this->house->organization->contractors()->create(['type' => ContractorType::Tko, 'name' => 'ООО «Вывоз-2»']);

    $r = $this->resolver->resolve(resolverCategory('waste.not_removed'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->name)->toContain('ООО «Вывоз»')
        ->and($r->name)->not->toContain('Вывоз-2')
        ->and($r->phone)->toBe('+7 843 000-00-07')
        ->and($r->isSure)->toBeTrue();
});

it('shows the party type instead of a name that still needs checking', function () {
    $this->house->organization->update(['direct_hot_water' => true]);
    ResponsibleParty::query()->where('type', ResponsibleType::RsoHot->value)->update(['name' => 'Поставщик горячей воды — уточнить']);

    $r = $this->resolver->resolve(resolverCategory('water.hot_none'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toBe(ResponsibleType::RsoHot->label())
        ->and($r->phone)->toBeNull()
        ->and($r->hint)->toContain('Телефон уточните в организации дома: +7 843 000-00-01');
});

it('shows the heat supplier name from the regional package as is', function () {
    $this->house->organization->update(['direct_hot_water' => true]);

    $r = $this->resolver->resolve(resolverCategory('water.hot_none'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toBe('Теплоснабжающая организация по дому (Татэнерго или Казэнерго)')
        ->and($r->phone)->toBeNull();
});

it('keeps a direct contract request with the organization when the package has no supplier', function () {
    ResponsibleParty::query()->where('type', ResponsibleType::RsoCold->value)->delete();
    $this->house->organization->update(['direct_cold_water' => true]);

    $r = $this->resolver->resolve(resolverCategory('water.cold_none'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Organization)
        ->and($r->isSure)->toBeFalse()
        ->and($r->hint)->toContain('прямой договор');
});

it('sends a lift without a contractor to the regional party with the organization phone', function () {
    $r = $this->resolver->resolve(resolverCategory('lift.broken'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->isSure)->toBeFalse()
        ->and($r->phone)->toBeNull()
        ->and($r->hint)->toContain('Телефон уточните в организации дома: +7 843 000-00-01');
});

it('sends yard problems to the municipal party', function () {
    $r = $this->resolver->resolve(resolverCategory('other.yard'), $this->house);

    expect($r->kind)->toBe(ResponsibleKind::Party)
        ->and($r->name)->toContain('Открытая Казань')
        ->and($r->phone)->toBe('+7 843 236-41-23')
        ->and($r->isSure)->toBeTrue()
        ->and($r->hint)->not->toContain('Телефон уточните');
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
        ->and($water->hint)->not->toContain('Телефон уточните')
        ->and($light->kind)->toBe(ResponsibleKind::Unknown)
        ->and($light->isSure)->toBeFalse();
});
