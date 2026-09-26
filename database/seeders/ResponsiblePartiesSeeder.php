<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\ResponsibleParty;
use Database\Seeders\Support\CsvReader;
use Illuminate\Database\Seeder;

class ResponsiblePartiesSeeder extends Seeder
{
    public const REGION_CODE = 'RU-TA';

    public function run(): void
    {
        $this->seedRows(CsvReader::rows('region-ru-ta.csv'));
    }

    /** @param iterable<array<string,string|null>> $rows */
    public function seedRows(iterable $rows): void
    {
        foreach ($rows as $row) {
            ResponsibleParty::query()->updateOrCreate(
                ['region_code' => self::REGION_CODE, 'type' => trim((string) $row['type'])],
                [
                    'name' => trim((string) $row['name']),
                    'phone' => CsvReader::strOrNull($row['phone'] ?? null),
                    'url' => CsvReader::strOrNull($row['url'] ?? null),
                    'note' => CsvReader::strOrNull($row['note'] ?? null),
                    'source' => 'manual',
                    'source_date' => '2026-09-17',
                ],
            );
        }
    }
}
