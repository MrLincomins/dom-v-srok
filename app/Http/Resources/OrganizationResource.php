<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Organizations\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
final class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'phone_ads' => $this->phone_ads,
            'phone_dispatch' => $this->phone_dispatch,
            'email' => $this->email,
            'reception_hours' => $this->reception_hours,
            'reception_address' => $this->reception_address,
            'direct_contracts' => [
                'cold_water' => $this->direct_cold_water,
                'hot_water' => $this->direct_hot_water,
                'heat' => $this->direct_heat,
                'power' => $this->direct_power,
                'tko' => $this->direct_tko,
            ],
            'is_demo' => $this->is_demo,
        ];
    }
}
