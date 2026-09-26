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
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2026-08-31')))->toBeFalse()
        ->and($calendar->isWorkingDay(CarbonImmutable::parse('2026-11-05')))->toBeTrue();
});

it('warns when the calendar ends within a year', function () {
    Log::spy();

    (new CalendarSeeder)->seedRows([['day' => CarbonImmutable::today()->addMonths(2)->toDateString(), 'is_working' => '0', 'note' => 'тест']]);

    Log::shouldHaveReceived('warning')->once()->with('calendar.short', Mockery::type('array'));
});
