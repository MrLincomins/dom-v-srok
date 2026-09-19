<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHouseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'entrances' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'chat_keywords_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
