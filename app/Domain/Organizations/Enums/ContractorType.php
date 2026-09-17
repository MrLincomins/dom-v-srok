<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Enums;

enum ContractorType: string
{
    case Lift = 'lift';
    case Intercom = 'intercom';
    case Tko = 'tko';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Lift => 'Лифтовая организация',
            self::Intercom => 'Домофонная компания',
            self::Tko => 'Региональный оператор ТКО',
            self::Other => 'Подрядчик',
        };
    }
}
