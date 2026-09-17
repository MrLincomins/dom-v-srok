<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bot\Client\MaxClient;
use Illuminate\Console\Command;

final class BotUnsubscribe extends Command
{
    protected $signature = 'bot:unsubscribe';

    protected $description = 'удалить все подписки на вебхуки (после этого работает long polling)';

    public function handle(MaxClient $client): int
    {
        foreach ($client->subscriptions() as $subscription) {
            $url = (string) ($subscription['url'] ?? '');
            if ($url !== '') {
                $client->unsubscribe($url);
                $this->info('Удалена подписка '.$url);
            }
        }

        return self::SUCCESS;
    }
}
