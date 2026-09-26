<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Requests\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin Attachment */
final class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'mime' => $this->mime,
            'url' => $this->path !== ''
                ? URL::temporarySignedRoute('api.attachments.show', now()->addMinutes((int) config('attachments.url_ttl_minutes')), ['attachment' => $this->id])
                : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
