<?php

declare(strict_types=1);

namespace App\Domain\Demo;

use App\Bot\Models\BotSession;
use App\Bot\Models\OutboxMessage;
use App\Domain\Organizations\Models\Contractor;
use App\Domain\Organizations\Models\Executor;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Requests\Models\EmergencySignal;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class DemoResetService
{
    public function reset(): void
    {
        DB::transaction(function (): void {
            $organizationIds = Organization::query()->where('is_demo', true)->pluck('id');
            $houseIds = House::query()->where('is_demo', true)->orWhereIn('organization_id', $organizationIds)->pluck('id');
            $requestIds = ServiceRequest::query()
                ->whereIn('organization_id', $organizationIds)
                ->orWhereIn('house_id', $houseIds)
                ->pluck('id');

            $files = Attachment::query()->whereIn('request_id', $requestIds)->where('path', '!=', '')->get(['disk', 'path']);
            OutboxMessage::query()->whereIn('request_id', $requestIds)->delete();
            ServiceRequest::query()->whereIn('id', $requestIds)->delete();

            EmergencySignal::query()->whereIn('house_id', $houseIds)->orWhereIn('organization_id', $organizationIds)->delete();
            $this->clearBotSessions($organizationIds, $houseIds, $requestIds);
            $this->pruneStaff($organizationIds);

            if (ServiceRequest::query()->count() === 0) {
                foreach (['requests_id_seq', 'request_events_id_seq', 'attachments_id_seq'] as $sequence) {
                    DB::statement("ALTER SEQUENCE {$sequence} RESTART WITH 1");
                }
            }

            (new DemoSeeder)->run();

            DB::afterCommit(fn () => $files->each(fn (Attachment $file) => Storage::disk($file->disk)->delete($file->path)));
            Log::info('demo.reset', ['organizations' => $organizationIds->count(), 'requests_removed' => $requestIds->count()]);
        });
    }

    /**
     * @param  Collection<int, mixed>  $organizationIds
     * @param  Collection<int, mixed>  $houseIds
     * @param  Collection<int, mixed>  $requestIds
     */
    private function clearBotSessions(Collection $organizationIds, Collection $houseIds, Collection $requestIds): void
    {
        $maxUserIds = User::query()
            ->whereNotNull('max_user_id')
            ->where(fn ($q) => $q->whereIn('house_id', $houseIds)->orWhereIn('organization_id', $organizationIds))
            ->pluck('max_user_id');

        BotSession::query()
            ->whereIn('max_user_id', $maxUserIds)
            ->orWhereIn(DB::raw("payload->>'repeat_of_id'"), $requestIds->map(fn (mixed $id): string => (string) $id)->all())
            ->delete();
    }

    /** @param  Collection<int, mixed>  $organizationIds */
    private function pruneStaff(Collection $organizationIds): void
    {
        $executorNames = array_column(DemoSeeder::EXECUTORS, 'name');
        Executor::query()->whereIn('organization_id', $organizationIds)->whereNotIn('name', $executorNames)->delete();

        Contractor::query()->whereIn('organization_id', $organizationIds)->get()
            ->reject(fn (Contractor $c) => collect(DemoSeeder::CONTRACTORS)->contains(fn (array $seeded) => $seeded['type'] === $c->type && $seeded['name'] === $c->name))
            ->each(fn (Contractor $c) => $c->delete());
    }
}
