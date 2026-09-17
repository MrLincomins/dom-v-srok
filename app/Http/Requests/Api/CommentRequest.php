<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class CommentRequest extends FormRequest
{
    public function rules(): array
    {
        return ['text' => ['required', 'string', 'max:1000']];
    }
}
