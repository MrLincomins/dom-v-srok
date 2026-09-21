<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Requests\RequestQuery;
use App\Domain\Requests\RequestService;
use Illuminate\Console\Command;

final class RequestsOverdueScan extends Command
{
    protected $signature = 'requests:overdue-scan';

    protected $description = 'отметить заявки, у которых вышел срок, и предупредить диспетчеров организации';

    public function handle(RequestQuery $query, RequestService $service): int
    {
        $marked = 0;
        foreach ($query->newlyOverdue()->get() as $request) {
            if ($service->markOverdue($request) !== null) {
                $marked++;
            }
        }
        $this->info('Срок вышел у заявок: '.$marked);

        return self::SUCCESS;
    }
}
