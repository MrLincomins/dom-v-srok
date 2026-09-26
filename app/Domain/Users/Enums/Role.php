<?php

declare(strict_types=1);

namespace App\Domain\Users\Enums;

enum Role: string
{
    case Resident = 'resident';
    case Dispatcher = 'dispatcher';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Resident => 'Житель',
            self::Dispatcher => 'Диспетчер',
            self::Admin => 'Администратор организации',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Resident;
    }
}
