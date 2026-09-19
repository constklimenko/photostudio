<?php

namespace Tests\Feature\Console;

use PDO;
use Tests\TestCase;

class DatabaseTransferTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/db-transfer-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->dir);

        parent::tearDown();
    }

    public function test_backup_creates_uncompressed_dump_file(): void
    {
        $source = $this->dir.'/source.sqlite';
        $this->seedSqlite($source);
        $dump = $this->dir.'/backup.sql';

        $this->artisan('db:backup', [
            '--database' => $this->connection($source),
            '--path' => $dump,
            '--no-compress' => true,
        ])->assertSuccessful();

        $this->assertFileExists($dump);
        $this->assertStringContainsString('Вася', (string) file_get_contents($dump));
    }

    public function test_backup_compresses_dump_with_gzip(): void
    {
        $source = $this->dir.'/source.sqlite';
        $this->seedSqlite($source);
        $dump = $this->dir.'/backup';

        $this->artisan('db:backup', [
            '--database' => $this->connection($source),
            '--path' => $dump,
        ])->assertSuccessful();

        $this->assertFileExists($dump.'.gz');
        $this->assertStringContainsString('Вася', gzdecode((string) file_get_contents($dump.'.gz')));
    }

    public function test_restore_replaces_target_database(): void
    {
        $source = $this->dir.'/source.sqlite';
        $this->seedSqlite($source);
        $dump = $this->dir.'/backup.sql';

        $this->artisan('db:backup', [
            '--database' => $this->connection($source),
            '--path' => $dump,
            '--no-compress' => true,
        ])->assertSuccessful();

        $target = $this->dir.'/target.sqlite';

        $this->artisan('db:restore', [
            'dump' => $dump,
            '--database' => $this->connection($target),
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(['Вася'], $this->readSqlite($target));
    }

    public function test_restore_fails_when_dump_missing(): void
    {
        $this->artisan('db:restore', [
            'dump' => $this->dir.'/missing.sql',
            '--database' => $this->connection($this->dir.'/target.sqlite'),
            '--force' => true,
        ])->assertFailed();
    }

    protected function connection(string $database): string
    {
        config([
            'database.connections.transfer_test' => array_merge(
                config('database.connections.sqlite'),
                ['database' => $database]
            ),
        ]);

        return 'transfer_test';
    }

    protected function seedSqlite(string $path): void
    {
        $pdo = new PDO('sqlite:'.$path);
        $pdo->exec('CREATE TABLE people (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO people (name) VALUES ('Вася')");
    }

    protected function readSqlite(string $path): array
    {
        $pdo = new PDO('sqlite:'.$path);

        return $pdo->query('SELECT name FROM people ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    }
}
