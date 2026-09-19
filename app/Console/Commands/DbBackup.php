<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

final class DbBackup extends Command
{
    protected $signature = 'db:backup {--keep=7 : Сколько последних копий хранить}';

    protected $description = 'pg_dump базы в storage/app/backups, старые копии удаляются';

    public function handle(): int
    {
        $db = (array) config('database.connections.pgsql');
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $file = $dir.'/dom-'.now()->format('Ymd-His').'.sql.gz';

        $command = sprintf(
            'set -o pipefail; pg_dump -h %s -p %s -U %s %s | gzip > %s',
            escapeshellarg((string) $db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg((string) $db['username']),
            escapeshellarg((string) $db['database']),
            escapeshellarg($file),
        );
        $result = Process::env(['PGPASSWORD' => (string) $db['password']])->timeout(600)->run($command);

        if (! $result->successful()) {
            File::delete($file);
            $this->error('pg_dump не удался: '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        $old = collect((array) File::glob($dir.'/dom-*.sql.gz'))->sort()->reverse()->slice((int) $this->option('keep'));
        foreach ($old as $path) {
            File::delete($path);
        }
        $this->info('Сохранено: '.$file);

        return self::SUCCESS;
    }
}
