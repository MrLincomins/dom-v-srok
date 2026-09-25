<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Dto\JournalPeriod;
use App\Domain\Requests\JournalExporter;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JournalRequest;
use App\Http\Resources\JournalRowResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

final class JournalController extends Controller
{
    use ResolvesOrganization;

    private const LINK_TTL_MINUTES = 3;

    public function __construct(private readonly JournalExporter $journal) {}

    public function index(JournalRequest $request): AnonymousResourceCollection
    {
        $organization = $this->organizationOf($request);
        $period = $this->period($request, $organization);
        $page = $this->journal->rows($organization, $period)->paginate(min((int) $request->validated('per_page', 50), 100));

        return JournalRowResource::collection($page)->additional(['meta' => [
            'period' => ['from' => $period->fromDate(), 'to' => $period->toDate()],
            'summary' => $this->journal->summary($organization, $period),
        ]]);
    }

    public function csv(JournalRequest $request): Response
    {
        $organization = $this->organizationOf($request);

        return $this->csvResponse($organization, $this->period($request, $organization), (int) $request->user()?->id);
    }

    public function csvLink(JournalRequest $request): JsonResponse
    {
        $organization = $this->organizationOf($request);
        $period = $this->period($request, $organization);
        $expiresAt = now()->addMinutes(self::LINK_TTL_MINUTES);

        return response()->json(['data' => [
            'url' => URL::temporarySignedRoute('api.journal.export', $expiresAt, [
                'organization' => $organization->id,
                'user' => $request->user()?->id,
                'from' => $period->fromDate(),
                'to' => $period->toDate(),
            ]),
            'expires_at' => $expiresAt->toIso8601String(),
        ]]);
    }

    public function export(Request $request): Response
    {
        $organization = Organization::query()->findOrFail($request->integer('organization'));
        $user = User::query()->find($request->integer('user'));
        if ($user === null || ! $user->isStaff() || $user->organization_id !== $organization->id) {
            throw new AuthorizationException;
        }
        $period = JournalPeriod::fromDates($request->string('from')->value(), $request->string('to')->value(), $organization->region->timezone);

        return $this->csvResponse($organization, $period, $user->id);
    }

    private function csvResponse(Organization $organization, JournalPeriod $period, int $userId): Response
    {
        Log::info('journal.exported', ['organization_id' => $organization->id, 'user_id' => $userId]);

        return response($this->journal->csv($organization, $period), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="zhurnal-zayavok-'.$period->fromDate().'-'.$period->toDate().'.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function period(JournalRequest $request, Organization $organization): JournalPeriod
    {
        return JournalPeriod::fromDates($request->validated('from'), $request->validated('to'), $organization->region->timezone);
    }
}
