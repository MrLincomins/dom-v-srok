<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum DeadlineUnit: string
{
    case Hours = 'hours';
    case Days = 'days';
    case WorkingDays = 'working_days';
}
