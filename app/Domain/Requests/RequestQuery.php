<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;

/** очередь диспетчера: фильтры, просрочка, поиск, сортировка по сроку */
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
        $this->applySearch($q, $search);

        return $q->orderByRaw('deadline_fix_at ASC NULLS LAST')->orderBy('created_at');
    }

    /** @return Builder<ServiceRequest> */
    public function newlyOverdue(): Builder
    {
        return ServiceRequest::query()
            ->overdue()
            ->whereDoesntHave('events', fn (Builder $events) => $events
                ->where('type', EventType::Reminder->value)
                ->where('payload->kind', 'overdue'))
            ->orderBy('id');
    }

    /** счётчики для вкладок */
    public function counters(int $organizationId, ?string $search = null): array
    {
        $base = function () use ($organizationId, $search): Builder {
            $q = ServiceRequest::query()->forOrganization($organizationId);
            $this->applySearch($q, $search);

            return $q;
        };

        return [
            'new' => $base()->where('status', RequestStatus::New->value)->count(),
            'in_progress' => $base()->active()->count(),
            'overdue' => $base()->overdue()->count(),
            'closed' => $base()->closed()->count(),
        ];
    }

    /** @param Builder<ServiceRequest> $q */
    private function applySearch(Builder $q, ?string $search): void
    {
        if ($search === null || trim($search) === '') {
            return;
        }

        $term = trim($search);
        $q->where(function (Builder $w) use ($term): void {
            if (ctype_digit($term)) {
                $w->orWhere('id', (int) $term);
            }
            $w->orWhere('flat', $term)
                ->orWhere('description', 'ilike', '%'.$term.'%')
                ->orWhereHas('house', fn (Builder $h) => $h->where('address', 'ilike', '%'.$term.'%'));
        });
    }
}
