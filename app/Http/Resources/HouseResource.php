<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Organizations\Models\House;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin House */
final class HouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'address' => $this->address,
            'entrances' => $this->entrances,
            'qr_token' => $this->qr_token,
            'chat_bound' => $this->max_chat_id !== null,
        ];
    }
}
