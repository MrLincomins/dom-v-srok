<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;
use App\Bot\Models\ProcessedUpdate;
use App\Bot\Updates\Update;
use App\Jobs\ProcessMaxUpdate;
use App\Support\Models\AppSetting;
use Illuminate\Console\Command;

final class BotPoll extends Command
{
    protected $signature = 'bot:poll {--once : Один запрос и выход} {--timeout=30 : Тайм-аут long polling, секунд}';

    protected $description = 'получать обновления через поллинг и их обработка';

    public function handle(MaxClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('MAX_BOT_TOKEN не задан');

            return self::FAILURE;
        }
        if (config('max.mode') === 'webhook') {
            $this->warn('MAX_MODE=webhook: при активной подписке long polling не работает. Сначала bot:unsubscribe.');
        }

        $marker = AppSetting::get('poll_marker');
        $marker = $marker === null ? null : (int) $marker;
        $this->info('Поллинг запущен, marker='.($marker ?? 'null'));

        do {
            try {
                $batch = $client->getUpdates($marker, 100, (int) $this->option('timeout'), config('max.update_types'));
            } catch (MaxApiException $e) {
                $this->error($e->getMessage());
                sleep(5);

                continue;
            }

            foreach ($batch['updates'] as $raw) {
                $update = Update::fromArray($raw);
                if (ProcessedUpdate::query()->insertOrIgnore(['update_key' => $update->key(), 'received_at' => now()]) === 0) {
                    continue;
                }
                $this->line('← '.$update->type.' '.$update->key());
                ProcessMaxUpdate::dispatchSync($raw);
            }

            if ($batch['marker'] !== null) {
                $marker = $batch['marker'];
                AppSetting::put('poll_marker', $marker);
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
