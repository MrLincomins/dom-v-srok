<?php

declare(strict_types=1);

namespace App\Domain\Organizations;

use App\Domain\Organizations\Models\AccessCode;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class AccessCodeService
{
    public function redeem(User $user, string $code): ?Organization
    {
        return DB::transaction(function () use ($user, $code): ?Organization {
            $accessCode = AccessCode::query()->lockForUpdate()->where('code_hash', AccessCode::hash($code))->first();
            if ($accessCode === null || ! $accessCode->isUsable()) {
                return null;
            }

            $accessCode->used_count += 1;
            $accessCode->save();

            $user->role = $accessCode->role;
            $user->organization_id = $accessCode->organization_id;
            $user->save();

            return $accessCode->organization;
        });
    }
}
