<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Organizations\Dto\HouseData;
use App\Domain\Organizations\Models\House;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class StoreHouseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:200', $this->uniqueInOrganization(...)],
            'entrances' => ['required', 'integer', 'min:1', 'max:50'],
            'chat_keywords_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['address' => 'адрес'];
    }

    private function uniqueInOrganization(string $attribute, mixed $value, Closure $fail): void
    {
        $address = HouseData::normalizeAddress((string) $value);
        if ($address === '') {
            $fail('Укажите адрес дома');

            return;
        }
        $wanted = mb_strtolower($address);
        $taken = House::query()
            ->where('organization_id', $this->user()?->organization_id)
            ->pluck('address')
            ->contains(fn (string $existing): bool => mb_strtolower(HouseData::normalizeAddress($existing)) === $wanted);
        if ($taken) {
            $fail('Дом с таким адресом уже есть в вашей организации');
        }
    }
}
