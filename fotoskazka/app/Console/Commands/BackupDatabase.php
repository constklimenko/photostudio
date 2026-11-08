<?php

namespace App\Console\Commands;

use App\Services\DatabaseTransferService;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup
                            {--database= : Database connection to back up}
                            {--path= : Output file path (default: storage/app/backups/DB-Y-m-d_His.sql)}
                            {--no-compress : Do not gzip-compress the dump}
                            {--prune=7 : Keep only the N most recent backups in the default directory (0 = keep all)}';

    protected $description = 'Create a full backup of the database for transfer between copies';

    public function handle(DatabaseTransferService $service): int
    {
        $connection = $this->resolveConnection();
        $compress = ! (bool) $this->option('no-compress');
        $path = $this->option('path');
        $defaultLocation = $path === null;

        try {
            $path ??= $service->defaultPath($connection);
            $result = $service->backup($connection, $path, $compress);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup created: '.$result['path'].' ('.$this->formatSize($result['size']).').');

        $keep = max(0, (int) $this->option('prune'));

        if ($defaultLocation && $keep > 0) {
            $removed = $service->prune($connection, $keep);

            if ($removed > 0) {
                $this->warn('Pruned old backups: '.$removed.'.');
            }
        }

        return self::SUCCESS;
    }

    protected function resolveConnection(): string
    {
        if ($connection = $this->option('database')) {
            return (string) $connection;
        }

        return (string) config('database.default');
    }

    protected function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];

        $size = $bytes / 1024;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 1).' '.$units[$unit];
    }
}
