<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\Models\Organization;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;

final class OrganizationCardController extends Controller
{
    public function show(Organization $organization): OrganizationResource
    {
        return new OrganizationResource($organization);
    }
}
