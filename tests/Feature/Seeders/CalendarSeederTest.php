<?php

declare(strict_types=1);

use App\Domain\Catalog\WorkCalendar;
use Carbon\CarbonImmutable;
use Database\Seeders\CalendarSeeder;
use Illuminate\Support\Facades\Log;

it('treats tatarstan holidays as days off', function () {
    Log::spy();
    $this->seed(CalendarSeeder::class);
    $calendar = new WorkCalendar;

    expect($calendar->isWorkingDay(CarbonImmutable::parse('2026-11-06')))->toBeFalse()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2026-03-20')))->toBeFalse()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2026-05-27')))->toBeFalse()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2026-11-05')))->toBeTrue();
});

it('gives no extra day off when a tatarstan holiday falls on a weekend', function () {
    Log::spy();
    $this->seed(CalendarSeeder::class);
    $calendar = new WorkCalendar;

    expect($calendar->isWorkingDay(CarbonImmutable::parse('2026-08-30')))->toBeFalse()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2026-08-31')))->toBeTrue()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2027-11-08')))->toBeTrue()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2027-05-17')))->toBeTrue();
});

it('follows the federal day off transfers for 2027', function () {
    Log::spy();
    $this->seed(CalendarSeeder::class);
    $calendar = new WorkCalendar;

    expect($calendar->isWorkingDay(CarbonImmutable::parse('2027-02-20')))->toBeTrue()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2027-02-22')))->toBeFalse()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2027-11-05')))->toBeFalse()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2027-12-31')))->toBeFalse();
});

it('warns when the calendar ends within a year', function () {
    Log::spy();

    (new CalendarSeeder)->seedRows([['day' => CarbonImmutable::today()->addMonths(2)->toDateString(), 'is_working' => '0', 'note' => 'тест']]);

    Log::shouldHaveReceived('warning')->once()->with('calendar.short', Mockery::type('array'));
});
