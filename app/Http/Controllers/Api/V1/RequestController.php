<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\Models\Executor;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Dto\ClosingPhoto;
use App\Domain\Requests\Dto\RedirectData;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestQuery;
use App\Domain\Requests\RequestService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignRequest;
use App\Http\Requests\Api\ChangeStatusRequest;
use App\Http\Requests\Api\CloseRequest;
use App\Http\Requests\Api\CommentRequest;
use App\Http\Requests\Api\ConfirmRequest;
use App\Http\Requests\Api\QueueRequest;
use App\Http\Requests\Api\RedirectRequest;
use App\Http\Resources\RequestListResource;
use App\Http\Resources\RequestResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;

final class RequestController extends Controller
{
    public function __construct(private readonly RequestService $service, private readonly RequestQuery $query) {}

    public function index(QueueRequest $request): AnonymousResourceCollection
    {
        $organizationId = (int) $request->user()->organization_id;
        $page = $this->query->queue(
            $organizationId,
            $request->validated('status'),
            $request->boolean('overdue'),
            $request->validated('house_id') !== null ? (int) $request->validated('house_id') : null,
            $request->validated('q'),
        )->paginate(min((int) $request->validated('per_page', 30), 100));

        return RequestListResource::collection($page)->additional(['meta' => ['counters' => $this->query->counters($organizationId)]]);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): RequestResource
    {
        $this->authorize('view', $serviceRequest);

        return new RequestResource($serviceRequest->load(['category', 'house', 'executor', 'events.actor', 'attachments', 'responsibleParty', 'redirectedParty']));
    }

    public function changeStatus(ChangeStatusRequest $request, ServiceRequest $serviceRequest): RequestResource
    {
        $this->authorize('manage', $serviceRequest);
        $updated = $this->service->transition(
            $serviceRequest,
            RequestStatus::from($request->validated('status')),
            Actor::dispatcher($request->user()),
            $request->validated('comment'),
        );

        return $this->card($updated);
    }

    public function assign(AssignRequest $request, ServiceRequest $serviceRequest): RequestResource
    {
        $this->authorize('manage', $serviceRequest);
        $executor = Executor::query()->where('organization_id', $request->user()->organization_id)->where('is_active', true)->findOrFail((int) $request->validated('executor_id'));
        $updated = $this->service->assign($serviceRequest, $executor, Actor::dispatcher($request->user()), $request->validated('comment'));

        return $this->card($updated);
    }

    public function close(CloseRequest $request, ServiceRequest $serviceRequest): RequestResource
    {
        $this->authorize('manage', $serviceRequest);
        $photos = array_map(
            fn (UploadedFile $file): ClosingPhoto => new ClosingPhoto($file, (string) $file->getMimeType()),
            array_values($request->file('photos', [])),
        );
        $updated = $this->service->close($serviceRequest, Actor::dispatcher($request->user()), $request->validated('comment'), $photos);

        return $this->card($updated);
    }

    public function redirect(RedirectRequest $request, ServiceRequest $serviceRequest): RequestResource
    {
        $this->authorize('manage', $serviceRequest);
        $updated = $this->service->redirect($serviceRequest, Actor::dispatcher($request->user()), new RedirectData(
            partyId: $request->validated('responsible_party_id') !== null ? (int) $request->validated('responsible_party_id') : null,
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            note: $request->validated('note'),
            contractorId: $request->validated('contractor_id') !== null ? (int) $request->validated('contractor_id') : null,
        ));

        return $this->card($updated);
    }

    public function comment(CommentRequest $request, ServiceRequest $serviceRequest): RequestResource
    {
        $this->authorize('manage', $serviceRequest);
        $this->service->comment($serviceRequest, Actor::dispatcher($request->user()), $request->validated('text'));

        return $this->card($serviceRequest->refresh());
    }

    public function confirm(ConfirmRequest $request, ServiceRequest $serviceRequest): RequestResource
    {
        $this->authorize('confirm', $serviceRequest);
        $actor = Actor::resident($request->user());
        $updated = $request->boolean('resolved')
            ? $this->service->confirm($serviceRequest, $actor, ConfirmedBy::Resident, $request->validated('comment'))
            : $this->service->returnToWork($serviceRequest, $actor, $request->validated('comment'));

        return $this->card($updated);
    }

    public function my(Request $request): AnonymousResourceCollection
    {
        $userId = (int) $request->user()->id;
        $page = ServiceRequest::query()->with(['category', 'house', 'executor'])
            ->where(fn (Builder $q) => $q->where('resident_user_id', $userId)
                ->orWhereHas('participants', fn (Builder $p) => $p->where('user_id', $userId)))
            ->latest('id')
            ->paginate(30);

        return RequestListResource::collection($page);
    }

    private function card(ServiceRequest $request): RequestResource
    {
        return new RequestResource($request->load(['category', 'house', 'executor', 'events.actor', 'attachments', 'responsibleParty', 'redirectedParty']));
    }
}
