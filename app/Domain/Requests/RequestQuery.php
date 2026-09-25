<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\RequestEvent;
use App\Domain\Requests\Models\ServiceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class RequestQuery
{
    /** @return Builder<ServiceRequest> */
    public function queue(int $organizationId, ?string $status = null, bool $overdueOnly = false, ?int $houseId = null, ?string $search = null): Builder
    {
        $q = ServiceRequest::query()
            ->with(['category', 'house', 'executor'])
            ->forOrganization($organizationId);

        match (true) {
            $status === null, $status === '' => null,
            $status === 'open' => $q->open(),
            $status === 'active' => $q->active(),
            $status === 'closed' => $q->closed(),
            default => $q->where('status', RequestStatus::from($status)->value),
        };

        if ($overdueOnly) {
            $q->overdue();
        }
        if ($houseId !== null) {
            $q->where('house_id', $houseId);
        }
        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->where(function (Builder $w) use ($term, $like): void {
                if (ctype_digit($term)) {
                    $w->orWhere('id', (int) $term);
                }
                $w->orWhere('flat', $term)
                    ->orWhere('description', 'ilike', $like)
                    ->orWhereHas('house', fn (Builder $h) => $h->where('address', 'ilike', $like));
            });
        }

        return $q->orderByRaw('deadline_fix_at ASC NULLS LAST')->orderBy('created_at');
    }

    /** @return Builder<ServiceRequest> */
    public function newlyOverdue(): Builder
    {
        return ServiceRequest::query()
            ->overdue()
            ->whereDoesntHave('events', fn (Builder $events) => $events
                ->where('type', EventType::Reminder->value)
                ->where('payload->kind', RequestEvent::OVERDUE_KIND))
            ->orderBy('id');
    }

    /** @return Builder<ServiceRequest> */
    public function dueSoon(int $withinHours = 2): Builder
    {
        $now = CarbonImmutable::now();

        return ServiceRequest::query()
            ->open()
            ->where('deadline_fix_at', '>=', $now)
            ->where('deadline_fix_at', '<=', $now->addHours($withinHours))
            ->whereDoesntHave('events', fn (Builder $events) => $events
                ->where('type', EventType::Reminder->value)
                ->where('payload->kind', RequestEvent::DUE_SOON_KIND))
            ->orderBy('id');
    }

    /** @return array{new:int,in_progress:int,overdue:int,closed:int} */
    public function counters(int $organizationId): array
    {
        $base = fn () => ServiceRequest::query()->forOrganization($organizationId);

        return [
            'new' => $base()->where('status', RequestStatus::New->value)->count(),
            'in_progress' => $base()->active()->count(),
            'overdue' => $base()->overdue()->count(),
            'closed' => $base()->closed()->count(),
        ];
    }
}
