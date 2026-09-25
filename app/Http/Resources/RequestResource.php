<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Requests\Enums\AttachmentKind;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Requests\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceRequest
 */
final class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $full = $this->isFullyVisibleTo($request->user());

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'allowed_transitions' => array_map(fn ($s) => $s->value, $this->status->allowedTransitions()),
            'category' => ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug],
            'description' => $full ? $this->description : null,
            'house' => new HouseResource($this->house),
            'entrance' => $full ? $this->entrance : null,
            'flat' => $full ? $this->flat : null,
            'responsible' => [
                'kind' => $this->responsible_kind->value,
                'name' => $this->responsible_name,
                'phone' => $this->responsible_phone,
                'is_sure' => $this->is_sure,
                'party_id' => $this->responsible_party_id,
            ],
            'deadline_fix_at' => $this->deadline_fix_at?->toIso8601String(),
            'deadline_reply_at' => $this->deadline_reply_at?->toIso8601String(),
            'basis' => $this->basis,
            'is_overdue' => $this->isOverdue(),
            'closed_late' => $this->closedLate(),
            'executor' => $this->executor ? new ExecutorResource($this->executor) : null,
            'first_reaction_at' => $this->first_reaction_at?->toIso8601String(),
            'done_at' => $this->done_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'confirmed_by' => $this->confirmed_by?->value,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'returned_count' => $this->returned_count,
            'redirected_to' => $this->when($this->status === RequestStatus::Redirected, fn () => [
                'name' => $this->redirectedToName(),
                'phone' => $this->redirectedToPhone(),
                'party' => $this->redirectedParty ? ['id' => $this->redirectedParty->id, 'name' => $this->redirectedParty->name, 'phone' => $this->redirectedParty->phone] : null,
                'note' => $this->redirect_note,
            ]),
            'participants_count' => $this->participants_count,
            'origin' => $this->origin->value,
            'rating' => $this->rating,
            'events' => EventResource::collection($this->whenLoaded('events')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments', fn () => $full
                ? $this->attachments
                : $this->attachments->reject(fn (Attachment $attachment) => $attachment->kind === AttachmentKind::Resident)->values())),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
