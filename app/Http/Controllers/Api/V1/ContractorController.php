<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\Dto\ContractorData;
use App\Domain\Organizations\OrganizationService;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreContractorRequest;
use App\Http\Resources\ContractorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ContractorController extends Controller
{
    use ResolvesOrganization;

    public function __construct(private readonly OrganizationService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ContractorResource::collection($this->organizationOf($request)->contractors()->orderBy('type')->orderBy('name')->get());
    }

    public function store(StoreContractorRequest $request): JsonResponse
    {
        $contractor = $this->service->addContractor($this->organizationOf($request), ContractorData::fromArray($request->validated()));

        return (new ContractorResource($contractor))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $contractor): JsonResponse
    {
        $this->service->removeContractor($this->organizationOf($request)->contractors()->findOrFail((int) $contractor));

        return response()->json(['data' => ['ok' => true]]);
    }
}
