<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Models\AppSetting;
use Illuminate\Console\Command;

/** сид справочников и демо данные один раз, отмечает в app_settings. */
final class DemoSeedOnce extends Command
{
    protected $signature = 'demo:seed-once {--force : Не спрашивать подтверждения в проде}';

    protected $description = 'заполнить справочники и демо организацию, если это ещё не делалось';

    public function handle(): int
    {
        if (AppSetting::get('seeded_at') !== null) {
            $this->info('Сид уже выполнялся: '.AppSetting::get('seeded_at'));

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--force' => (bool) $this->option('force')]);
        AppSetting::put('seeded_at', now()->toIso8601String());
        $this->info('Готово.');

        return self::SUCCESS;
    }
}
