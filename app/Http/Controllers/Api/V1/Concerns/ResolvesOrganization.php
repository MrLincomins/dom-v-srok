<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Domain\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

trait ResolvesOrganization
{
    protected function organizationOf(Request $request): Organization
    {
        $organization = $request->user()?->organization;
        if ($organization === null) {
            throw (new ModelNotFoundException)->setModel(Organization::class);
        }

        return $organization;
    }
}
