<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Models\AppSetting;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Console\Command;

final class DemoSeedOnce extends Command
{
    protected $signature = 'demo:seed-once {--force : Не спрашивать подтверждения в проде}';

    protected $description = 'справочники из docs/*.csv при каждом запуске, демо организация один раз';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->call('db:seed', ['--class' => ReferenceSeeder::class, '--force' => $force]);

        if (AppSetting::get('seeded_at') !== null) {
            $this->info('Демо уже создано: '.AppSetting::get('seeded_at'));

            return self::SUCCESS;
        }

        if (! config('demo.seed')) {
            $this->info('Справочники обновлены, демо не создаётся: DEMO_SEED выключен.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => $force]);
        AppSetting::put('seeded_at', now()->toIso8601String());
        $this->info('Готово.');

        return self::SUCCESS;
    }
}
