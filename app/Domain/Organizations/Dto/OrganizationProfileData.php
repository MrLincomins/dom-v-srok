<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Dto;

use App\Domain\Organizations\Models\Organization;

final readonly class OrganizationProfileData
{
    public function __construct(
        public string $name,
        public string $phoneAds,
        public ?string $phoneDispatch,
        public ?string $email,
        public ?string $receptionHours,
        public ?string $receptionAddress,
        public bool $directColdWater,
        public bool $directHotWater,
        public bool $directHeat,
        public bool $directPower,
        public bool $directTko,
    ) {}

    /** @param array<string,mixed> $changes поля PATCH /organization, остальное из текущей карточки */
    public static function fromPatch(Organization $organization, array $changes): self
    {
        $direct = is_array($changes['direct_contracts'] ?? null) ? $changes['direct_contracts'] : [];
        $text = static fn (string $key, ?string $current): ?string => array_key_exists($key, $changes) ? self::nullable($changes[$key]) : $current;

        return new self(
            name: (string) ($changes['name'] ?? $organization->name),
            phoneAds: (string) ($changes['phone_ads'] ?? $organization->phone_ads),
            phoneDispatch: $text('phone_dispatch', $organization->phone_dispatch),
            email: $text('email', $organization->email),
            receptionHours: $text('reception_hours', $organization->reception_hours),
            receptionAddress: $text('reception_address', $organization->reception_address),
            directColdWater: (bool) ($direct['cold_water'] ?? $organization->direct_cold_water),
            directHotWater: (bool) ($direct['hot_water'] ?? $organization->direct_hot_water),
            directHeat: (bool) ($direct['heat'] ?? $organization->direct_heat),
            directPower: (bool) ($direct['power'] ?? $organization->direct_power),
            directTko: (bool) ($direct['tko'] ?? $organization->direct_tko),
        );
    }

    /** @return array<string,mixed> */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'phone_ads' => $this->phoneAds,
            'phone_dispatch' => $this->phoneDispatch,
            'email' => $this->email,
            'reception_hours' => $this->receptionHours,
            'reception_address' => $this->receptionAddress,
            'direct_cold_water' => $this->directColdWater,
            'direct_hot_water' => $this->directHotWater,
            'direct_heat' => $this->directHeat,
            'direct_power' => $this->directPower,
            'direct_tko' => $this->directTko,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
