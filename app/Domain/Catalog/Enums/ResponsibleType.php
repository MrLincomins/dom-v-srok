<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/** кто отвечает за категорию, значения те же, что в check у categories и responsible_parties */
enum ResponsibleType: string
{
    case Uk = 'uk';
    case RsoCold = 'rso_cold';
    case RsoHot = 'rso_hot';
    case RsoHeat = 'rso_heat';
    case RsoPower = 'rso_power';
    case Lift = 'lift';
    case Tko = 'tko';
    case Intercom = 'intercom';
    case Municipal = 'municipal';
    case CapitalRepair = 'capital_repair';
    case Ads = 'ads';

    public function label(): string
    {
        return match ($this) {
            self::Uk => 'Управляющая организация или ТСЖ',
            self::RsoCold => 'Водоканал (холодная вода)',
            self::RsoHot => 'Поставщик горячей воды',
            self::RsoHeat => 'Теплоснабжающая организация',
            self::RsoPower => 'Энергосбыт',
            self::Lift => 'Лифтовая организация',
            self::Tko => 'Региональный оператор ТКО',
            self::Intercom => 'Домофонная компания',
            self::Municipal => 'Муниципальная служба',
            self::CapitalRepair => 'Фонд капитального ремонта',
            self::Ads => 'Аварийно-диспетчерская служба',
        };
    }

    public function isRso(): bool
    {
        return in_array($this, [self::RsoCold, self::RsoHot, self::RsoHeat, self::RsoPower], true);
    }

    /** колонка direct_* у организации - прямой договор с этой рсо */
    public function directContractColumn(): ?string
    {
        return match ($this) {
            self::RsoCold => 'direct_cold_water',
            self::RsoHot => 'direct_hot_water',
            self::RsoHeat => 'direct_heat',
            self::RsoPower => 'direct_power',
            self::Tko => 'direct_tko',
            default => null,
        };
    }

    /** типы, где у организации может быть свой подрядчик */
    public function contractorType(): ?string
    {
        return match ($this) {
            self::Lift => 'lift',
            self::Intercom => 'intercom',
            self::Tko => 'tko',
            default => null,
        };
    }
}
