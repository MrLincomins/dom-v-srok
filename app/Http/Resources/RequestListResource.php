<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Requests\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * строка очереди
 *
 * @mixin ServiceRequest
 */
final class RequestListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'category' => $this->category->name,
            'description' => $this->description,
            'address' => $this->house->address,
            'entrance' => $this->entrance,
            'flat' => $this->flat,
            'responsible_name' => $this->responsible_name,
            'is_sure' => $this->is_sure,
            'deadline_fix_at' => $this->deadline_fix_at?->toIso8601String(),
            'deadline_reply_at' => $this->deadline_reply_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'executor' => $this->executor ? new ExecutorResource($this->executor) : null,
            'participants_count' => $this->participants_count,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
