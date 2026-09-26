<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Exceptions\InvalidTransition;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class RequestsAutoConfirm extends Command
{
    protected $signature = 'requests:auto-confirm {--hours=72 : Сколько часов заявка ждёт подтверждения жителя}';

    protected $description = 'закрыть выполненные заявки, по которым житель молчит дольше 72 часов';

    public function handle(RequestService $service): int
    {
        $before = CarbonImmutable::now()->subHours((int) $this->option('hours'));
        $requests = ServiceRequest::query()
            ->where('status', RequestStatus::Done->value)
            ->where('done_at', '<', $before)
            ->orderBy('id')
            ->get();

        $closed = 0;
        foreach ($requests as $request) {
            try {
                $service->confirm($request, Actor::system(), 'Подтверждено автоматически: три дня без ответа жителя');
                $closed++;
            } catch (InvalidTransition) {
                continue;
            }
        }
        $this->info('Закрыто автоматически: '.$closed);

        return self::SUCCESS;
    }
}
