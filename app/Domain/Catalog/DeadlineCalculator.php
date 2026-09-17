<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\Enums\DeadlineUnit;
use App\Domain\Catalog\Models\Category;
use Carbon\CarbonImmutable;

/** срок из справочника в дату, рабочие дни по календарю до 18:00 региона, отдаём utc */
final class DeadlineCalculator
{
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

        $deadline = match ($unit) {
            DeadlineUnit::Hours => $local->addHours($value),
            DeadlineUnit::Days => $local->addDays($value),
            DeadlineUnit::WorkingDays => $this->calendar->addWorkingDays($local, $value)->setTime(18, 0),
        };

        return $deadline->utc();
    }
}
