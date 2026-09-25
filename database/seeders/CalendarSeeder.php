<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\CalendarDay;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\CsvReader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CalendarSeeder extends Seeder
{
    public const MIN_HORIZON_DAYS = 365;

    public function run(): void
    {
        $this->seedRows(CsvReader::rows('calendar-2026-2027.csv'));
    }

    /** @param iterable<array<string,string|null>> $rows */
    public function seedRows(iterable $rows): void
    {
        foreach ($rows as $row) {
            CalendarDay::query()->updateOrCreate(
                ['day' => trim((string) $row['day'])],
                ['is_working' => CsvReader::bool($row['is_working']), 'note' => CsvReader::strOrNull($row['note'] ?? null)],
            );
        }

        $this->warnIfShort();
    }

    private function warnIfShort(): void
    {
        $lastDay = CalendarDay::query()->max('day');
        $horizon = CarbonImmutable::today()->addDays(self::MIN_HORIZON_DAYS);

        if ($lastDay === null || CarbonImmutable::parse((string) $lastDay)->lessThan($horizon)) {
            Log::warning('calendar.short', ['last_day' => $lastDay, 'needed_until' => $horizon->toDateString()]);
        }
    }
}
