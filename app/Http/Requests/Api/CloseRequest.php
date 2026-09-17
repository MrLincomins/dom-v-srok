<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class CloseRequest extends FormRequest
{
    public function rules(): array
    {
        $maxKb = (int) config('attachments.max_mb') * 1024;

        return [
            'comment' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'mimetypes:'.implode(',', config('attachments.mimes')), 'max:'.$maxKb],
        ];
    }
}
