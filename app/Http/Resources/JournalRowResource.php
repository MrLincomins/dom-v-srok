<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Requests\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRequest */
final class JournalRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->toIso8601String(),
            'address' => $this->house->address,
            'entrance' => $this->entrance,
            'flat' => $this->flat,
            'resident_name' => $this->resident->name,
            'category' => $this->category->name,
            'description' => $this->description,
            'responsible_name' => $this->responsible_name,
            'responsible_phone' => $this->responsible_phone,
            'deadline_fix_at' => $this->deadline_fix_at?->toIso8601String(),
            'basis' => $this->basis,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'executor_name' => $this->executor?->name,
            'first_reaction_at' => $this->first_reaction_at?->toIso8601String(),
            'done_at' => $this->done_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'closed_late' => $this->closedLate(),
            'confirmed_by' => $this->confirmed_by?->value,
            'returned_count' => $this->returned_count,
            'redirected_to' => $this->redirectedToName(),
            'participants_count' => $this->participants_count,
        ];
    }
}
