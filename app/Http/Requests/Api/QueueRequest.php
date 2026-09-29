<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Requests\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QueueRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $overdue = $this->query('overdue');

        if (! is_string($overdue)) {
            return;
        }

        $flag = filter_var($overdue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($flag !== null) {
            $this->merge(['overdue' => $flag]);
        }
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['open', 'active', 'closed', ...array_column(RequestStatus::cases(), 'value')])],
            'overdue' => ['nullable', 'boolean'],
            'house_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
