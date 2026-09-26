<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Requests\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeStatusRequest extends FormRequest
{
    public const STATUSES = [RequestStatus::Assigned, RequestStatus::InProgress, RequestStatus::Done, RequestStatus::Confirmed];

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_map(fn (RequestStatus $status) => $status->value, self::STATUSES))],
            'comment' => ['nullable', 'string', 'max:1000', Rule::requiredIf(fn () => $this->input('status') === RequestStatus::Confirmed->value)],
        ];
    }
}
