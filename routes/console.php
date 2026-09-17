<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// планировщик, потом сюда outbox:requeue-stale, requests:overdue-scan, requests:auto-confirm
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('model:prune')->daily();
