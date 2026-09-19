<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrganizationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'phone_ads' => ['sometimes', 'required', 'string', 'max:32'],
            'phone_dispatch' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email', 'max:254'],
            'reception_hours' => ['sometimes', 'nullable', 'string', 'max:500'],
            'reception_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'direct_contracts' => ['sometimes', 'array:cold_water,hot_water,heat,power,tko'],
            'direct_contracts.cold_water' => ['sometimes', 'boolean'],
            'direct_contracts.hot_water' => ['sometimes', 'boolean'],
            'direct_contracts.heat' => ['sometimes', 'boolean'],
            'direct_contracts.power' => ['sometimes', 'boolean'],
            'direct_contracts.tko' => ['sometimes', 'boolean'],
        ];
    }
}
