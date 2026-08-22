<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaTestStorage extends Command
{
    protected $signature = 'media:test-storage {--disk=yandex_disk : Имя диска для проверки}';

    protected $description = 'Проверка подключения к диску: запись, чтение и удаление тестового файла';

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');

        $this->info("Проверка диска: {$diskName}");

        if (config("filesystems.disks.{$diskName}") === null) {
            $this->error("Диск [{$diskName}] не найден в config/filesystems.php.");

            return self::FAILURE;
        }

        $token = config("filesystems.disks.{$diskName}.token");

        if (array_key_exists('token', config("filesystems.disks.{$diskName}", [])) && blank($token)) {
            $this->error('OAuth-токен не настроен: заполните YANDEX_DISK_TOKEN в .env.');

            return self::FAILURE;
        }

        $root = config("filesystems.disks.{$diskName}.root");

        if ($root) {
            $this->line("Корневая директория: {$root}");
        }

        try {
            return $this->runCycle($diskName);
        } catch (\Throwable $e) {
            $this->error('Ошибка соединения или файловых операций: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function runCycle(string $diskName): int
    {
        $disk = Storage::disk($diskName);

        $testDir = '_storage-test';
        $path = $testDir.'/'.Str::uuid().'.txt';
        $content = 'Fotoskazka storage test — '.now()->toIso8601String();

        $disk->makeDirectory($testDir);
        $this->info("[OK] Тестовая директория готова: {$testDir}");

        $disk->put($path, $content);
        $this->info("[OK] Файл записан: {$path}");

        if (! $disk->exists($path)) {
            $this->error('Файл не найден после записи.');

            return self::FAILURE;
        }

        $read = $disk->get($path);

        if ($read !== $content) {
            $this->error('Содержимое файла не совпадает с записанным.');

            return self::FAILURE;
        }

        $this->info('[OK] Файл прочитан, содержимое совпадает.');

        $disk->delete($path);

        if ($disk->exists($path)) {
            $this->error('Не удалось удалить тестовый файл.');

            return self::FAILURE;
        }

        $this->info('[OK] Файл удалён.');
        $disk->deleteDirectory($testDir);
        $this->info("[OK] Тестовая директория удалена: {$testDir}");

        $this->newLine();
        $this->info("Хранилище [{$diskName}] работает корректно.");

        return self::SUCCESS;
    }
}
