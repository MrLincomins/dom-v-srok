<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum ActorRole: string
{
    case Resident = 'resident';
    case Dispatcher = 'dispatcher';
    case System = 'system';
}
