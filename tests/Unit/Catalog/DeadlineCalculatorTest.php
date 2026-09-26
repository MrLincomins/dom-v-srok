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
    $this->local = fn (int $value, DeadlineUnit $unit, string $from): ?string => $this->calc
        ->compute($value, $unit, CarbonImmutable::parse($from, 'Europe/Moscow'), 'Europe/Moscow')
        ?->setTimezone('Europe/Moscow')->format('Y-m-d H:i');
});

it('adds hours from the moment of creation', function () {
    $from = CarbonImmutable::parse('2026-09-17 10:00:00', 'UTC');
    expect($this->calc->compute(24, DeadlineUnit::Hours, $from, 'Europe/Moscow')?->toIso8601String())
        ->toBe('2026-09-18T10:00:00+00:00');
});

it('keeps hours exact after 18:00', function () {
    expect(($this->local)(4, DeadlineUnit::Hours, '2026-09-17 18:30'))->toBe('2026-09-17 22:30');
});

it('counts working days by the calendar and sets 18:00 local time', function () {
    $from = CarbonImmutable::parse('2026-09-18 09:00:00', 'Europe/Moscow');
    expect($this->calc->compute(3, DeadlineUnit::WorkingDays, $from, 'Europe/Moscow')?->toIso8601String())
        ->toBe('2026-09-24T15:00:00+00:00');
});

it('treats a transferred saturday as working', function () {
    expect(($this->local)(1, DeadlineUnit::WorkingDays, '2026-09-25 09:00'))->toBe('2026-09-26 18:00');
});

it('ends calendar days at 18:00 local time', function () {
    expect(($this->local)(1, DeadlineUnit::Days, '2026-09-17 17:59'))->toBe('2026-09-18 18:00')
        ->and(($this->local)(1, DeadlineUnit::Days, '2026-09-17 18:00'))->toBe('2026-09-19 18:00')
        ->and(($this->local)(1, DeadlineUnit::Days, '2026-09-17 18:01'))->toBe('2026-09-19 18:00')
        ->and(($this->local)(2, DeadlineUnit::Days, '2026-09-17 00:10'))->toBe('2026-09-19 18:00');
});

it('starts counting working days from the next day after 18:00', function () {
    expect(($this->local)(1, DeadlineUnit::WorkingDays, '2026-09-16 17:59'))->toBe('2026-09-17 18:00')
        ->and(($this->local)(1, DeadlineUnit::WorkingDays, '2026-09-16 18:01'))->toBe('2026-09-18 18:00');
});

it('carries a friday evening request over the weekend and the holiday', function () {
    expect(($this->local)(1, DeadlineUnit::WorkingDays, '2026-09-18 17:59'))->toBe('2026-09-22 18:00')
        ->and(($this->local)(1, DeadlineUnit::WorkingDays, '2026-09-18 18:01'))->toBe('2026-09-22 18:00')
        ->and(($this->local)(2, DeadlineUnit::WorkingDays, '2026-09-18 18:01'))->toBe('2026-09-23 18:00');
});

it('skips a holiday from the calendar', function () {
    CalendarDay::query()->insert(['day' => '2026-11-04', 'is_working' => false, 'note' => 'День народного единства']);
    $this->calc = new DeadlineCalculator(new WorkCalendar);

    expect(($this->local)(1, DeadlineUnit::WorkingDays, '2026-11-03 17:59'))->toBe('2026-11-05 18:00')
        ->and(($this->local)(1, DeadlineUnit::WorkingDays, '2026-11-03 18:01'))->toBe('2026-11-05 18:00')
        ->and(($this->local)(1, DeadlineUnit::Days, '2026-11-03 18:01'))->toBe('2026-11-05 18:00');
});

it('returns null when the catalog has no deadline', function () {
    expect($this->calc->compute(null, null, CarbonImmutable::now(), 'Europe/Moscow'))->toBeNull();
});
