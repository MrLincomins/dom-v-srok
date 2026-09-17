<?php

declare(strict_types=1);

use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Catalog\Enums\DeadlineUnit;
use App\Domain\Catalog\Models\CalendarDay;
use App\Domain\Catalog\WorkCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CalendarDay::query()->insert([
        ['day' => '2026-09-21', 'is_working' => false, 'note' => 'тестовый праздник в понедельник'],
        ['day' => '2026-09-26', 'is_working' => true, 'note' => 'тестовая рабочая суббота'],
    ]);
    $this->calc = new DeadlineCalculator(new WorkCalendar);
});

it('adds hours from the moment of creation', function () {
    $from = CarbonImmutable::parse('2026-09-17 10:00:00', 'UTC');
    expect($this->calc->compute(24, DeadlineUnit::Hours, $from, 'Europe/Moscow')?->toIso8601String())
        ->toBe('2026-09-18T10:00:00+00:00');
});

it('counts working days by the calendar and sets 18:00 local time', function () {
    // пятница 18.09 + 3 рабочих дня: пн 21.09 праздник, значит вт, ср, чт 24 в 18:00 мск = 15:00 utc
    $from = CarbonImmutable::parse('2026-09-18 09:00:00', 'Europe/Moscow');
    expect($this->calc->compute(3, DeadlineUnit::WorkingDays, $from, 'Europe/Moscow')?->toIso8601String())
        ->toBe('2026-09-24T15:00:00+00:00');
});

it('treats a transferred saturday as working', function () {
    $from = CarbonImmutable::parse('2026-09-25 09:00:00', 'Europe/Moscow'); // пятница
    expect($this->calc->compute(1, DeadlineUnit::WorkingDays, $from, 'Europe/Moscow')?->setTimezone('Europe/Moscow')->toDateString())
        ->toBe('2026-09-26');
});

it('returns null when the catalog has no deadline', function () {
    expect($this->calc->compute(null, null, CarbonImmutable::now(), 'Europe/Moscow'))->toBeNull();
});
