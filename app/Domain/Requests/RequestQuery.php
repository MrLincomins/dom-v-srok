<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\RequestEvent;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Enums\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
        $queueSearch = new QueueSearch($search ?? '');
        $queueSearch->apply($q);
        $number = $queueSearch->requestNumber();
        if ($number !== null) {
            $q->orderByRaw('(id = ?) DESC', [$number]);
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

    /** @return Builder<Organization> */
    public function organizationsWithMessageableStaff(): Builder
    {
        return Organization::query()
            ->whereHas('staff', fn (Builder $users) => $users
                ->where('role', '!=', Role::Resident->value)
                ->whereNotNull('max_user_id')
                ->whereNotNull('bot_started_at'))
            ->orderBy('id');
    }

    /** @return array{new:int,active:int,overdue:int,waiting:int} */
    public function digestCounters(int $organizationId): array
    {
        $base = fn () => ServiceRequest::query()->forOrganization($organizationId);

        return [
            'new' => $base()->where('status', RequestStatus::New->value)->count(),
            'active' => $base()->active()->count(),
            'overdue' => $base()->overdue()->count(),
            'waiting' => $base()->where('status', RequestStatus::Done->value)->count(),
        ];
    }

    /** @return Collection<int, ServiceRequest> */
    public function earliestOpen(int $organizationId, int $limit = 3): Collection
    {
        return ServiceRequest::query()
            ->with(['category', 'house.region'])
            ->forOrganization($organizationId)
            ->open()
            ->orderByRaw('deadline_fix_at ASC NULLS LAST')
            ->orderBy('id')
            ->limit($limit)
            ->get();
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
