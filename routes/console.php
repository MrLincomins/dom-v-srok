<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('outbox:requeue-stale')->everyMinute();
Schedule::command('requests:overdue-scan')->everyTenMinutes();
Schedule::command('requests:auto-confirm')->hourly();
Schedule::command('updates:prune')->daily();
Schedule::command('db:backup')->dailyAt('03:30');
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('model:prune')->daily();
