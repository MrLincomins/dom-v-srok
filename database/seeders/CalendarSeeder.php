<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\CalendarDay;
use Database\Seeders\Support\CsvReader;
use Illuminate\Database\Seeder;

/** исключения календаря из docs/calendar-2026-2027.csv */
class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CsvReader::rows('calendar-2026-2027.csv') as $row) {
            CalendarDay::query()->updateOrCreate(
                ['day' => trim((string) $row['day'])],
                ['is_working' => CsvReader::bool($row['is_working']), 'note' => CsvReader::strOrNull($row['note'])],
            );
        }
    }
}
