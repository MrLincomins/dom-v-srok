<?php

declare(strict_types=1);

use App\Domain\Catalog\Enums\ResponsibleType;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\ResponsibleParty;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\ResponsiblePartiesSeeder;
use Database\Seeders\Support\CsvReader;

/** @return list<array<string,string|null>> */
function catalogRows(): array
{
    return array_values(iterator_to_array(CsvReader::rows('catalog.csv'), false));
}

it('renames a regional party of the same type without a duplicate', function () {
    $this->seed(ReferenceSeeder::class);
    $seeder = new ResponsiblePartiesSeeder;
    $row = ['type' => 'rso_cold', 'name' => 'Водоканал старое имя', 'phone' => '', 'url' => '', 'note' => ''];

    $seeder->seedRows([$row]);
    $seeder->seedRows([[...$row, 'name' => 'Водоканал новое имя', 'phone' => '+7 843 000-00-09']]);

    $parties = ResponsibleParty::query()->where('region_code', 'RU-TA')->where('type', ResponsibleType::RsoCold->value)->get();
    expect($parties)->toHaveCount(1)
        ->and($parties->first()->name)->toBe('Водоканал новое имя')
        ->and($parties->first()->phone)->toBe('+7 843 000-00-09');
});

it('deactivates categories that left the catalog together with their children', function () {
    $this->seed(ReferenceSeeder::class);
    $rows = catalogRows();
    $leaf = collect($rows)->first(fn (array $r) => $r['parent_slug'] !== '' && $r['parent_slug'] !== null);
    $root = collect($rows)->first(fn (array $r) => ($r['parent_slug'] ?? '') === '' && $r['slug'] !== $leaf['parent_slug']);

    (new CatalogSeeder)->seedRows(array_values(array_filter(
        $rows,
        fn (array $r) => $r['slug'] !== $leaf['slug'] && $r['slug'] !== $root['slug'] && $r['parent_slug'] !== $root['slug'],
    )));

    $rootChildren = Category::query()->where('parent_id', Category::query()->where('slug', $root['slug'])->value('id'))->get();
    expect(Category::query()->where('slug', $leaf['slug'])->value('is_active'))->toBeFalse()
        ->and(Category::query()->where('slug', $root['slug'])->value('is_active'))->toBeFalse()
        ->and($rootChildren)->not->toBeEmpty()
        ->and($rootChildren->every(fn (Category $c) => ! $c->is_active))->toBeTrue()
        ->and(Category::query()->where('slug', $leaf['parent_slug'])->value('is_active'))->toBeTrue();

    (new CatalogSeeder)->seedRows($rows);

    expect(Category::query()->where('is_active', false)->count())->toBe(0);
});
