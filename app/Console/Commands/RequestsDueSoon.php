<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Requests\RequestQuery;
use App\Domain\Requests\RequestService;
use Illuminate\Console\Command;

final class RequestsDueSoon extends Command
{
    protected $signature = 'requests:due-soon';

    protected $description = 'предупредить диспетчеров организации, что до срока заявки меньше двух часов';

    public function handle(RequestQuery $query, RequestService $service): int
    {
        $marked = 0;
        foreach ($query->dueSoon()->get() as $request) {
            if ($service->markDueSoon($request) !== null) {
                $marked++;
            }
        }
        $this->info('Срок скоро выйдет у заявок: '.$marked);

        return self::SUCCESS;
    }
}
