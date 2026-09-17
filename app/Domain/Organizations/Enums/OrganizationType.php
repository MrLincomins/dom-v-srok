<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Enums;

enum OrganizationType: string
{
    case Uk = 'uk';
    case Tsj = 'tsj';
    case Jsk = 'jsk';

    public function label(): string
    {
        return match ($this) {
            self::Uk => 'Управляющая компания',
            self::Tsj => 'ТСЖ',
            self::Jsk => 'ЖСК',
        };
    }
}
