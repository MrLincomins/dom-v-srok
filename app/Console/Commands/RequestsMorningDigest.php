<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Requests\Events\MorningDigestDue;
use App\Domain\Requests\RequestQuery;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class RequestsMorningDigest extends Command
{
    protected $signature = 'requests:morning-digest';

    protected $description = 'утренняя сводка по заявкам диспетчерам каждой организации';

    public function handle(RequestQuery $query): int
    {
        $sent = 0;
        foreach ($query->organizationsWithMessageableStaff()->with('region')->get() as $organization) {
            event(new MorningDigestDue(
                $organization,
                $query->digestCounters($organization->id),
                $query->earliestOpen($organization->id),
                CarbonImmutable::now()->setTimezone($organization->region->timezone),
            ));
            $sent++;
        }
        $this->info('Сводка для организаций: '.$sent);

        return self::SUCCESS;
    }
}
