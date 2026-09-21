<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class JournalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $from = $this->input('from');
            $to = $this->input('to');
            if (! is_string($from) || ! is_string($to) || $validator->errors()->isNotEmpty()) {
                return;
            }
            if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 366) {
                $validator->errors()->add('to', 'Период журнала не длиннее года');
            }
        }];
    }
}
