<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Organizations\Enums\ContractorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreContractorRequest extends FormRequest
{
    public function rules(): array
    {
        $organizationId = (int) $this->user()?->organization_id;
        $type = (string) $this->input('type', '');

        return [
            'type' => ['required', Rule::enum(ContractorType::class)],
            'name' => [
                'required', 'string', 'max:200',
                Rule::unique('contractors', 'name')->where('organization_id', $organizationId)->where('type', $type),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }
}
