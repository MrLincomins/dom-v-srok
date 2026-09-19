<?php

declare(strict_types=1);

namespace App\Domain\Demo;

use App\Bot\Models\OutboxMessage;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Requests\Models\ServiceRequest;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** сброс демо, только организации с is_demo */
final class DemoResetService
{
    public function reset(): void
    {
        DB::transaction(function (): void {
            $organizationIds = Organization::query()->where('is_demo', true)->pluck('id');
            $requestIds = ServiceRequest::query()->whereIn('organization_id', $organizationIds)->pluck('id');

            Attachment::query()->whereIn('request_id', $requestIds)->where('path', '!=', '')->get()
                ->each(fn (Attachment $attachment) => Storage::disk($attachment->disk)->delete($attachment->path));
            OutboxMessage::query()->whereIn('request_id', $requestIds)->delete();
            ServiceRequest::query()->whereIn('id', $requestIds)->delete(); // события, участники, вложения удаляются каскадом

            // если других заявок нет, номера снова с 1: в CHECK.md и DATA-API.yaml они зафиксированы
            if (ServiceRequest::query()->count() === 0) {
                foreach (['requests_id_seq', 'request_events_id_seq', 'attachments_id_seq'] as $sequence) {
                    DB::statement("ALTER SEQUENCE {$sequence} RESTART WITH 1");
                }
            }
        });

        (new DemoSeeder)->run();
    }
}
