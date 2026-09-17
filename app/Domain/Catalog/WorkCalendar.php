<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\Models\CalendarDay;
use Carbon\CarbonImmutable;

/** рабочие дни пн-пт плюс исключения из calendar_days */
final class WorkCalendar
{
    /** @var array<string,bool>|null */
    private ?array $exceptions = null;

    public function isWorkingDay(CarbonImmutable $day): bool
    {
        $exception = $this->exceptions()[$day->toDateString()] ?? null;

        return $exception ?? $day->isWeekday();
    }

    public function addWorkingDays(CarbonImmutable $from, int $days): CarbonImmutable
    {
        $day = $from;
        for ($added = 0; $added < $days;) {
            $day = $day->addDay();
            if ($this->isWorkingDay($day)) {
                $added++;
            }
        }

        return $day;
    }

    /** @return array<string,bool> */
    private function exceptions(): array
    {
        return $this->exceptions ??= CalendarDay::query()
            ->get()
            ->mapWithKeys(fn (CalendarDay $d) => [$d->day->toDateString() => $d->is_working])
            ->all();
    }
}
