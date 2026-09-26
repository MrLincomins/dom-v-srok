<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Responsible;
use App\Domain\Organizations\Models\House;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ResponsibleResource extends JsonResource
{
    public function __construct(
        private readonly Category $category,
        private readonly House $house,
        private readonly Responsible $responsible,
        private readonly ?CarbonImmutable $fix,
        private readonly ?CarbonImmutable $reply,
    ) {
        parent::__construct($category);
    }

    public function toArray(Request $request): array
    {
        return [
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'is_emergency' => $this->category->is_emergency,
            ],
            'house_id' => $this->house->id,
            'responsible' => [
                'kind' => $this->responsible->kind->value,
                'name' => $this->responsible->name,
                'phone' => $this->responsible->phone,
                'is_sure' => $this->responsible->isSure,
                'hint' => $this->responsible->hint,
            ],
            'deadline_fix_at' => $this->fix?->toIso8601String(),
            'deadline_reply_at' => $this->reply?->toIso8601String(),
            'basis' => $this->category->basis,
        ];
    }
}
