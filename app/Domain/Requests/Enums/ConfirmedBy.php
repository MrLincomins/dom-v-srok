<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum ConfirmedBy: string
{
    case Resident = 'resident';
    case Auto = 'auto';
    case Dispatcher = 'dispatcher';

    public static function forRole(ActorRole $role): self
    {
        return match ($role) {
            ActorRole::Resident => self::Resident,
            ActorRole::Dispatcher => self::Dispatcher,
            ActorRole::System => self::Auto,
        };
    }
}
