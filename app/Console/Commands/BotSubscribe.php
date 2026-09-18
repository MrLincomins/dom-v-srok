<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bot\Client\MaxClient;
use App\Support\Models\AppSetting;
use Illuminate\Console\Command;

/** подписка на вебхук, старые сносятся, регистрирует APP_URL + /max/webhook с секретом. */
final class BotSubscribe extends Command
{
    protected $signature = 'bot:subscribe';

    protected $description = 'подписать бота на обновления через вебхук (только https)';

    public function handle(MaxClient $client): int
    {
        $url = rtrim((string) config('app.url'), '/').config('max.webhook_path');
        $secret = (string) config('max.webhook_secret');

        if (! str_starts_with($url, 'https://')) {
            $this->error('Вебхук принимается только по HTTPS: APP_URL='.config('app.url'));

            return self::FAILURE;
        }
        if (strlen($secret) < 16) {
            $this->error('MAX_WEBHOOK_SECRET должен быть не короче 16 символов');

            return self::FAILURE;
        }

        $alive = false;
        foreach ($client->subscriptions() as $subscription) {
            $old = (string) ($subscription['url'] ?? '');
            if ($old === $url) {
                $alive = true;

                continue;
            }
            if ($old !== '') {
                $client->unsubscribe($old);
                $this->line('Удалена подписка '.$old);
            }
        }
        if ($alive) {
            $this->info('Уже подписан: '.$url);

            return self::SUCCESS;
        }

        $result = $client->subscribe($url, config('max.update_types'), $secret);
        AppSetting::put('webhook', ['url' => $url, 'subscribed_at' => now()->toIso8601String(), 'result' => $result]);
        $this->info('Подписан: '.$url.' → '.json_encode($result, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
