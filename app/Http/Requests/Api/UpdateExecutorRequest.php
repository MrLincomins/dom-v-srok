<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateExecutorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}
