<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bot\Client\MaxClient;
use Illuminate\Console\Command;

final class BotInfo extends Command
{
    protected $signature = 'bot:info';

    protected $description = 'проверка токена - информация о боте и текущие подписки';

    public function handle(MaxClient $client): int
    {
        $me = $client->getMe();
        $this->info(sprintf('Бот: %s (@%s), id %s', $me['first_name'] ?? '?', $me['username'] ?? '?', $me['user_id'] ?? '?'));
        $this->line('Подписки: '.json_encode($client->subscriptions(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
