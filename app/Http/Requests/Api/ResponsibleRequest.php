<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ResponsibleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'min:1'],
            'house_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Выберите, что случилось',
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isEmpty() && $this->houseId() === null) {
                $validator->errors()->add('house_id', 'Укажите дом');
            }
        }];
    }

    public function categoryId(): int
    {
        return $this->integer('category_id');
    }

    public function houseId(): ?int
    {
        if ($this->filled('house_id')) {
            return $this->integer('house_id');
        }
        $user = $this->user();

        return $user instanceof User ? $user->house_id : null;
    }
}
