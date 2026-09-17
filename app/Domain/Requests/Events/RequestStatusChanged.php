<?php

declare(strict_types=1);

namespace App\Domain\Requests\Events;

use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\RequestEvent;
use App\Domain\Requests\Models\ServiceRequest;

final readonly class RequestStatusChanged
{
    public function __construct(
        public ServiceRequest $request,
        public RequestStatus $from,
        public RequestStatus $to,
        public Actor $by,
        public RequestEvent $event,
    ) {}
}
