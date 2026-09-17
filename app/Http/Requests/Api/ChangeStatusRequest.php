<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Requests\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_column(RequestStatus::cases(), 'value'))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
