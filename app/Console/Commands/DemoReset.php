<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Demo\DemoResetService;
use Illuminate\Console\Command;

/** сброс демо данных, реальные организации не трогает. */
final class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'сброс заявок демо организаций к исходным данным';

    public function handle(DemoResetService $service): int
    {
        $service->reset();
        $this->info('Данные пересозданы.');

        return self::SUCCESS;
    }
}
