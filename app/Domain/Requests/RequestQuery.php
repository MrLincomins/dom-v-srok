<?php

declare(strict_types=1);

namespace App\Domain\Requests;

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
            $q->where(function (Builder $w) use ($term): void {
                if (ctype_digit($term)) {
                    $w->orWhere('id', (int) $term);
                }
                $w->orWhere('flat', $term)
                    ->orWhere('description', 'ilike', '%'.$term.'%')
                    ->orWhereHas('house', fn (Builder $h) => $h->where('address', 'ilike', '%'.$term.'%'));
            });
        }

        return $q->orderByRaw('deadline_fix_at ASC NULLS LAST')->orderBy('created_at');
    }

    /** счётчики для вкладок */
    public function counters(int $organizationId): array
    {
        $base = fn () => ServiceRequest::query()->forOrganization($organizationId);

        return [
            'new' => $base()->where('status', RequestStatus::New->value)->count(),
            'in_progress' => $base()->whereIn('status', [RequestStatus::Assigned->value, RequestStatus::InProgress->value, RequestStatus::Returned->value, RequestStatus::Done->value])->count(),
            'overdue' => $base()->overdue()->count(),
            'closed' => $base()->closed()->count(),
        ];
    }
}
