<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Requests\Models\RequestEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RequestEvent */
final class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'actor_role' => $this->actor_role->value,
            'actor_name' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'comment' => $this->comment,
            'payload' => (object) $this->payload,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
