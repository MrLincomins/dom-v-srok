<?php

declare(strict_types=1);

namespace App\Domain\Requests\Dto;

use Carbon\CarbonImmutable;

final readonly class JournalPeriod
{
    private function __construct(public CarbonImmutable $from, public CarbonImmutable $to) {}

    public static function fromDates(?string $from, ?string $to, string $timezone): self
    {
        $end = $to !== null ? CarbonImmutable::parse($to, $timezone) : CarbonImmutable::now($timezone);
        $start = $from !== null ? CarbonImmutable::parse($from, $timezone) : $end->startOfMonth();

        return new self($start->startOfDay(), $end->endOfDay());
    }

    public function fromDate(): string
    {
        return $this->from->toDateString();
    }

    public function toDate(): string
    {
        return $this->to->toDateString();
    }

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    public function bounds(): array
    {
        return [$this->from->utc(), $this->to->utc()];
    }
}
