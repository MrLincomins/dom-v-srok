<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Requests\Dto\JournalPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class JournalRequest extends FormRequest
{
    private const MAX_PERIOD_DAYS = 366;

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
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $from = $this->input('from');
            $to = $this->input('to');
            $from = is_string($from) ? $from : null;
            $to = is_string($to) ? $to : null;
            if ($from === null && $to === null) {
                return;
            }
            $timezone = $this->user()?->organization?->region->timezone ?? 'Europe/Moscow';
            $period = JournalPeriod::fromDates($from, $to, $timezone);
            $field = $to !== null ? 'to' : 'from';
            if ($period->from->greaterThan($period->to)) {
                $validator->errors()->add($field, 'Начало периода не может быть позже конца');
            } elseif ($period->from->startOfDay()->diffInDays($period->to->startOfDay()) > self::MAX_PERIOD_DAYS) {
                $validator->errors()->add($field, 'Период журнала не длиннее года');
            }
        }];
    }
}
