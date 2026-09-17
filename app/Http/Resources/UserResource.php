<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            'organization' => $this->whenLoaded('organization', fn () => $this->organization ? new OrganizationResource($this->organization) : null),
            'house' => $this->whenLoaded('house', fn () => $this->house ? new HouseResource($this->house) : null),
            'entrance' => $this->entrance,
            'flat' => $this->flat,
            'is_demo' => $this->is_demo,
        ];
    }
}
