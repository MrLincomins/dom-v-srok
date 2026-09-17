<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\ResponsibleParty;
use Database\Seeders\Support\CsvReader;
use Illuminate\Database\Seeder;

/** региональный пакет татарстана из docs/region-ru-ta.csv */
class ResponsiblePartiesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CsvReader::rows('region-ru-ta.csv') as $row) {
            ResponsibleParty::query()->updateOrCreate(
                ['region_code' => 'RU-TA', 'type' => trim((string) $row['type']), 'name' => trim((string) $row['name'])],
                [
                    'phone' => CsvReader::strOrNull($row['phone']),
                    'url' => CsvReader::strOrNull($row['url']),
                    'note' => CsvReader::strOrNull($row['note']),
                    'source' => 'manual',
                    'source_date' => '2026-09-17',
                ],
            );
        }
    }
}
