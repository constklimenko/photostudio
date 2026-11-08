<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class DatabaseTransferService
{
    public function directory(): string
    {
        $directory = storage_path('app/backups');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return $directory;
    }

    public function defaultPath(string $connection): string
    {
        $name = $this->sanitize($this->databaseName($connection));

        return $this->directory().'/'.$name.'-'.date('Y-m-d_His').'.sql';
    }

    /**
     * @return array{path: string, size: int}
     */
    public function backup(string $connection, string $path, bool $compress): array
    {
        $config = $this->connectionConfig($connection);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $temp = tempnam($directory, '.db-backup-');

        if ($temp === false) {
            throw new RuntimeException('Unable to create a temporary dump file in ['.$directory.'].');
        }

        try {
            if (($config['driver'] ?? null) === 'sqlite') {
                $this->backupSqlite($config, $temp);
            } else {
                $this->backupMysql($config, $temp);
            }
        } catch (Throwable $exception) {
            @unlink($temp);

            throw $exception;
        }

        if ($compress) {
            $compressedPath = $path.'.gz';
            $result = $this->run('gzip -c '.escapeshellarg($temp).' > '.escapeshellarg($compressedPath), '');

            if (! $result->successful()) {
                @unlink($temp);
                @unlink($compressedPath);

                throw new RuntimeException('Compression failed: '.$result->errorOutput());
            }

            @unlink($temp);
            $path = $compressedPath;
        } else {
            rename($temp, $path);
        }

        return ['path' => $path, 'size' => filesize($path) ?: 0];
    }

    public function restore(string $connection, string $path): void
    {
        if (! is_file($path)) {
            throw new RuntimeException('Backup file not found: ['.$path.'].');
        }

        $config = $this->connectionConfig($connection);

        if (($config['driver'] ?? null) === 'sqlite') {
            $this->restoreSqlite($config, $path);

            return;
        }

        $this->restoreMysql($config, $path);
    }

    /**
     * @return array<int, array{filename: string, path: string, size: int, modified_at: int}>
     */
    public function backups(string $connection): array
    {
        $pattern = $this->sanitize($this->databaseName($connection)).'-*.sql*';
        $items = [];

        foreach (glob($this->directory().'/'.$pattern) ?: [] as $file) {
            if (is_file($file)) {
                $items[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => filesize($file) ?: 0,
                    'modified_at' => filemtime($file) ?: 0,
                ];
            }
        }

        usort($items, fn (array $a, array $b): int => $b['modified_at'] <=> $a['modified_at']);

        return $items;
    }

    /**
     * @return array{filename: string, path: string, size: int, modified_at: int}|null
     */
    public function latestBackup(string $connection): ?array
    {
        return $this->backups($connection)[0] ?? null;
    }

    public function prune(string $connection, int $keep): int
    {
        $removed = 0;

        foreach (array_slice($this->backups($connection), $keep) as $item) {
            if (@unlink($item['path'])) {
                $removed++;
            }
        }

        return $removed;
    }

    public function databaseName(string $connection): string
    {
        return (string) ($this->connectionConfig($connection)['database'] ?? $connection);
    }

    protected function backupMysql(array $config, string $temp): void
    {
        $args = array_merge($this->mysqlClientArgs($config), [
            '--no-tablespaces',
            '--single-transaction',
            '--quick',
        ]);

        $command = escapeshellarg($this->resolveBinary('mysqldump'))
            .' '.implode(' ', array_map('escapeshellarg', $args))
            .' '.escapeshellarg((string) $config['database'])
            .' > '.escapeshellarg($temp);

        $result = $this->run($command, (string) ($config['password'] ?? ''));

        if (! $result->successful()) {
            throw new RuntimeException('mysqldump failed: '.$result->errorOutput());
        }
    }

    protected function restoreMysql(array $config, string $path): void
    {
        $decompressor = str_ends_with($path, '.gz') ? 'gzip -dc' : 'cat';
        $args = $this->mysqlClientArgs($config);

        $command = $decompressor.' '.escapeshellarg($path)
            .' | '.escapeshellarg($this->resolveBinary('mysql'))
            .' '.implode(' ', array_map('escapeshellarg', $args))
            .' '.escapeshellarg((string) $config['database']);

        $result = $this->run($command, (string) ($config['password'] ?? ''));

        if (! $result->successful()) {
            throw new RuntimeException('mysql restore failed: '.$result->errorOutput());
        }
    }

    protected function backupSqlite(array $config, string $temp): void
    {
        $source = (string) $config['database'];

        if ($source === ':memory:') {
            throw new RuntimeException('Cannot back up an in-memory SQLite database.');
        }

        if (! is_file($source)) {
            throw new RuntimeException('SQLite database file not found: ['.$source.'].');
        }

        if (! copy($source, $temp)) {
            throw new RuntimeException('Unable to copy SQLite database file.');
        }
    }

    protected function restoreSqlite(array $config, string $path): void
    {
        $target = (string) $config['database'];

        if ($target === ':memory:') {
            throw new RuntimeException('Cannot restore into an in-memory SQLite database.');
        }

        $command = (str_ends_with($path, '.gz') ? 'gzip -dc' : 'cat')
            .' '.escapeshellarg($path)
            .' > '.escapeshellarg($target);

        $result = $this->run($command, '');

        if (! $result->successful()) {
            throw new RuntimeException('SQLite restore failed: '.$result->errorOutput());
        }
    }

    /**
     * @return array<int, string>
     */
    protected function mysqlClientArgs(array $config): array
    {
        $args = [];

        if (! empty($config['unix_socket'])) {
            $args[] = '--socket='.$config['unix_socket'];
        } else {
            if (! empty($config['host'])) {
                $args[] = '--host='.$config['host'];
            }

            if (($config['port'] ?? null) !== null && $config['port'] !== '') {
                $args[] = '--port='.$config['port'];
            }
        }

        if (! empty($config['username'])) {
            $args[] = '--user='.$config['username'];
        }

        $args[] = '--default-character-set=utf8mb4';

        return $args;
    }

    protected function connectionConfig(string $connection): array
    {
        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            throw new RuntimeException("Database connection [{$connection}] not found in config/database.php.");
        }

        return $config;
    }

    protected function resolveBinary(string $name): string
    {
        $which = $this->run(['which', $name], '');

        if (! $which->successful()) {
            throw new RuntimeException("Required binary '{$name}' not found. Install it (e.g. apt install {$name}/{$name}-client).");
        }

        return trim($which->output());
    }

    protected function run(array|string $command, string $password = ''): object
    {
        $pending = Process::env($password === '' ? [] : ['MYSQL_PWD' => $password])->timeout(0);

        return $pending->run($command);
    }

    protected function sanitize(string $name): string
    {
        $name = (string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);

        return trim($name, '._-') ?: 'database';
    }
}
