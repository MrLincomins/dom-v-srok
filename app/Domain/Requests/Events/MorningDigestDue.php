<?php

declare(strict_types=1);

namespace App\Domain\Requests\Events;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Models\ServiceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final readonly class MorningDigestDue
{
    /**
     * @param  array{new:int,active:int,overdue:int,waiting:int}  $counters
     * @param  Collection<int, ServiceRequest>  $top
     */
    public function __construct(public Organization $organization, public array $counters, public Collection $top, public CarbonImmutable $localDate) {}
}
