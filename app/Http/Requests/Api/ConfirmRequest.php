<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'resolved' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
