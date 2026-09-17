<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
final class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'is_emergency' => $this->is_emergency,
            'responsible_type' => $this->responsible_type?->value,
            'deadline_fix' => $this->hasFixDeadline() ? ['value' => $this->deadline_fix_value, 'unit' => $this->deadline_fix_unit->value] : null,
            'deadline_reply' => $this->hasReplyDeadline() ? ['value' => $this->deadline_reply_value, 'unit' => $this->deadline_reply_unit->value] : null,
            'basis' => $this->basis,
            'advice' => $this->advice_text,
            'verify' => $this->verify,
            'children' => $this->whenLoaded('children', fn () => self::collection($this->children)),
        ];
    }
}
