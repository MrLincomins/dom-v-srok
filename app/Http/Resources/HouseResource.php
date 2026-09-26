<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Organizations\Models\House;
use App\Support\Max\DeepLinks;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

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
            'chat_pinned' => $this->chat_pinned_message_id !== null,
            'chat_keywords_enabled' => $this->chat_keywords_enabled,
            'start_url' => app(DeepLinks::class)->houseStartUrl($this->resource),
            'qr_url' => URL::signedRoute('api.organization.houses.qr', ['house' => $this->id]),
        ];
    }
}
