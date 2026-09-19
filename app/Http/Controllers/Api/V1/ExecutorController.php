<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\Dto\ExecutorData;
use App\Domain\Organizations\Models\Executor;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\OrganizationService;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExecutorRequest;
use App\Http\Requests\Api\UpdateExecutorRequest;
use App\Http\Resources\ExecutorResource;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ExecutorController extends Controller
{
    use ResolvesOrganization;

    public function __construct(private readonly OrganizationService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ExecutorResource::collection($this->active($request)->orderBy('name')->get());
    }

    public function store(StoreExecutorRequest $request): JsonResponse
    {
        $executor = $this->service->addExecutor($this->organizationOf($request), ExecutorData::fromArray($request->validated()));

        return (new ExecutorResource($executor))->response()->setStatusCode(201);
    }

    public function update(UpdateExecutorRequest $request, string $executor): ExecutorResource
    {
        $model = $this->active($request)->findOrFail((int) $executor);

        return new ExecutorResource($this->service->updateExecutor($model, ExecutorData::fromPatch($model, $request->validated())));
    }

    public function destroy(Request $request, string $executor): JsonResponse
    {
        $this->service->archiveExecutor($this->active($request)->findOrFail((int) $executor));

        return response()->json(['data' => ['ok' => true]]);
    }

    /** @return HasMany<Executor, Organization> */
    private function active(Request $request): HasMany
    {
        return $this->organizationOf($request)->executors()->where('is_active', true);
    }
}
