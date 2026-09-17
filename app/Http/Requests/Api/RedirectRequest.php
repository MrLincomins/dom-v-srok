<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class RedirectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'responsible_party_id' => ['nullable', 'integer', 'required_without:name'],
            'name' => ['nullable', 'string', 'max:200', 'required_without:responsible_party_id'],
            'phone' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
