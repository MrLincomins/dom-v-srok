<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bot\Client\MaxClient;
use App\Bot\Texts\TextRepository;
use Illuminate\Console\Command;

final class BotCommands extends Command
{
    protected $signature = 'bot:commands';

    protected $description = 'задать команды бота для кнопки «Меню» в махе';

    public function handle(MaxClient $client, TextRepository $texts): int
    {
        if (! $client->isConfigured()) {
            $this->error('MAX_BOT_TOKEN не задан');

            return self::FAILURE;
        }

        $commands = [];
        foreach ((array) config('max.commands') as $name) {
            $commands[] = ['name' => (string) $name, 'description' => $texts->text('command.'.$name)];
        }

        $client->setCommands($commands);
        $this->info('Команды заданы: '.implode(', ', array_map(fn (array $c) => '/'.$c['name'], $commands)));

        return self::SUCCESS;
    }
}
