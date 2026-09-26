<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\Enums\DeadlineUnit;
use App\Domain\Catalog\Models\Category;
use Carbon\CarbonImmutable;

final class DeadlineCalculator
{
    public const END_OF_WORKDAY_HOUR = 18;

    public function __construct(private readonly WorkCalendar $calendar) {}

    public function fixDeadline(Category $category, CarbonImmutable $from, string $timezone): ?CarbonImmutable
    {
        return $this->compute($category->deadline_fix_value, $category->deadline_fix_unit, $from, $timezone);
    }

    public function replyDeadline(Category $category, CarbonImmutable $from, string $timezone): ?CarbonImmutable
    {
        return $this->compute($category->deadline_reply_value, $category->deadline_reply_unit, $from, $timezone);
    }

    public function compute(?int $value, ?DeadlineUnit $unit, CarbonImmutable $from, string $timezone): ?CarbonImmutable
    {
        if ($value === null || $unit === null) {
            return null;
        }

        $local = $from->setTimezone($timezone);
        $start = $local->hour >= self::END_OF_WORKDAY_HOUR ? $local->addDay()->startOfDay() : $local;

        $deadline = match ($unit) {
            DeadlineUnit::Hours => $local->addHours($value),
            DeadlineUnit::Days => $start->addDays($value)->setTime(self::END_OF_WORKDAY_HOUR, 0),
            DeadlineUnit::WorkingDays => $this->calendar->addWorkingDays($start, $value)->setTime(self::END_OF_WORKDAY_HOUR, 0),
        };

        return $deadline->utc();
    }
}
