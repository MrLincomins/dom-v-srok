<?php

declare(strict_types=1);

namespace App\Domain\Requests\Events;

use App\Domain\Requests\Models\ServiceRequest;

final readonly class RequestCreated
{
    public function __construct(public ServiceRequest $request) {}
}
