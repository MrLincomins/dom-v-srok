<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum ConfirmedBy: string
{
    case Resident = 'resident';
    case Auto = 'auto';
    case Dispatcher = 'dispatcher';
}
