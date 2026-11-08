<?php

namespace App\Console\Commands;

use App\Services\DatabaseTransferService;
use Illuminate\Console\Command;
use Throwable;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore
                            {dump? : Path to the backup file (.sql or .sql.gz). If omitted, the latest backup is used}
                            {--database= : Database connection to restore into}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Restore the database from a backup (fully replaces current data)';

    public function handle(DatabaseTransferService $service): int
    {
        $connection = $this->resolveConnection();
        $path = $this->resolveDumpPath($service, $connection);

        if ($path === null) {
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            sprintf('Restoring "%s" will overwrite database "%s". Continue?', basename($path), $service->databaseName($connection)),
            false
        )) {
            $this->info('Restore cancelled.');

            return self::SUCCESS;
        }

        try {
            $service->restore($connection, $path);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Database restored from '.basename($path).'.');

        return self::SUCCESS;
    }

    protected function resolveDumpPath(DatabaseTransferService $service, string $connection): ?string
    {
        if ($dump = $this->argument('dump')) {
            return $this->locate((string) $dump, $service);
        }

        $latest = $service->latestBackup($connection);

        if ($latest !== null) {
            return $latest['path'];
        }

        $backups = $service->backups($connection);

        if ($backups === []) {
            $this->error('No backups found in '.$service->directory().'.');

            return null;
        }

        $labels = array_map(
            fn (array $item): string => $item['filename'].' ('.$this->formatSize($item['size']).')',
            $backups
        );

        $selected = $this->choice('Select a backup to restore:', $labels);
        $index = array_search($selected, $labels, true);

        return $backups[$index]['path'];
    }

    protected function locate(string $path, DatabaseTransferService $service): string
    {
        if (is_file($path)) {
            return $path;
        }

        $candidate = $service->directory().'/'.basename($path);

        if (is_file($candidate)) {
            return $candidate;
        }

        return $path;
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
