<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Organizations\Models\Contractor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Contractor */
final class ContractorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'name' => $this->name,
            'phone' => $this->phone,
        ];
    }
}
