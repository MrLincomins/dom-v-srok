<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class StoreExecutorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:32'],
            'specialty' => ['nullable', 'string', 'max:64'],
        ];
    }
}
