<?php

declare(strict_types=1);

namespace App\Domain\Requests\Events;

use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;

final readonly class ParticipantJoined
{
    public function __construct(public ServiceRequest $request, public User $user) {}
}
