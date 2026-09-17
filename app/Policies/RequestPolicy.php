<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;

/** диспетчер видит только свою организацию, житель только свои заявки и где участник */
final class RequestPolicy
{
    public function view(User $user, ServiceRequest $request): bool
    {
        if ($user->isStaff()) {
            return $user->organization_id !== null && $user->organization_id === $request->organization_id;
        }

        return $request->resident_user_id === $user->id
            || $request->participants()->where('user_id', $user->id)->exists();
    }

    public function manage(User $user, ServiceRequest $request): bool
    {
        return $user->isStaff() && $user->organization_id !== null && $user->organization_id === $request->organization_id;
    }

    public function confirm(User $user, ServiceRequest $request): bool
    {
        return $request->resident_user_id === $user->id;
    }
}
