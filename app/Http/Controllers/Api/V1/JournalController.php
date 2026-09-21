<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Dto\JournalPeriod;
use App\Domain\Requests\JournalExporter;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JournalRequest;
use App\Http\Resources\JournalRowResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class JournalController extends Controller
{
    use ResolvesOrganization;

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
        $period = $this->period($request, $organization);

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
