<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\Dto\HouseData;
use App\Domain\Organizations\Dto\HouseSettingsData;
use App\Domain\Organizations\Dto\OrganizationProfileData;
use App\Domain\Organizations\OrganizationService;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreHouseRequest;
use App\Http\Requests\Api\UpdateHouseRequest;
use App\Http\Requests\Api\UpdateOrganizationRequest;
use App\Http\Resources\HouseResource;
use App\Http\Resources\OrganizationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OrganizationController extends Controller
{
    use ResolvesOrganization;

    public function __construct(private readonly OrganizationService $service) {}

    public function show(Request $request): OrganizationResource
    {
        return new OrganizationResource($this->organizationOf($request));
    }

    public function update(UpdateOrganizationRequest $request): OrganizationResource
    {
        $organization = $this->organizationOf($request);
        $data = OrganizationProfileData::fromPatch($organization, $request->validated());

        return new OrganizationResource($this->service->updateProfile($organization, $data));
    }

    public function houses(Request $request): AnonymousResourceCollection
    {
        return HouseResource::collection($this->organizationOf($request)->houses()->orderBy('address')->get());
    }

    public function storeHouse(StoreHouseRequest $request): JsonResponse
    {
        $house = $this->service->addHouse($this->organizationOf($request), HouseData::fromArray($request->validated()));

        return (new HouseResource($house))->response()->setStatusCode(201);
    }

    public function updateHouse(UpdateHouseRequest $request, string $house): HouseResource
    {
        $model = $this->organizationOf($request)->houses()->findOrFail((int) $house);
        $data = HouseSettingsData::fromPatch($model, $request->validated());

        return new HouseResource($this->service->updateHouse($model, $data));
    }
}
