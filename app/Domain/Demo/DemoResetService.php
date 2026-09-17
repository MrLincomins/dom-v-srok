<?php

declare(strict_types=1);

namespace App\Domain\Demo;

use App\Bot\Models\OutboxMessage;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Models\ServiceRequest;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;

/** сброс демо, только организации с is_demo */
final class DemoResetService
{
    public function reset(): void
    {
        DB::transaction(function (): void {
            $organizationIds = Organization::query()->where('is_demo', true)->pluck('id');
            $requestIds = ServiceRequest::query()->whereIn('organization_id', $organizationIds)->pluck('id');

            OutboxMessage::query()->whereIn('request_id', $requestIds)->delete();
            ServiceRequest::query()->whereIn('id', $requestIds)->delete(); // события, участники, вложения удаляются каскадом
        });

        (new DemoSeeder)->run();
    }
}
