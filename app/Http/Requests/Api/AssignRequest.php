<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class AssignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'executor_id' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
