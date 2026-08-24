<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Миграция одного Media: локальный оригинал → Яндекс.Диск.
 *
 * Порядок операций (критически важен):
 * upload → verify → DB update → delete local.
 * До успешной проверки удалённого файла и обновления записи БД
 * локальный оригинал гарантированно сохраняется.
 *
 * Команда идемпотентна: Media на целевом диске пропускаются,
 * существующий на Диске файл не перезаписывается — при совпадении
 * размера и содержимого он переиспользуется, при расхождении миграция
 * падает без изменения данных.
 */
class MigrateMediaToYandexDisk
{
    public const TARGET_DISK = 'yandex_disk';

    protected const DERIVATIVE_DISKS = ['thumbnails', 'image_cache'];

    public function __construct(protected ImageCacheService $imageCache) {}

    public function execute(Media $media): MediaMigrationResult
    {
        try {
            return $this->run($media);
        } catch (Throwable $exception) {
            Log::error('Media migration to Yandex Disk failed unexpectedly.', [
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => (string) $media->file_path,
                'error' => $exception->getMessage(),
            ]);

            return new MediaMigrationResult(MediaMigrationResult::FAILED, $exception->getMessage());
        }
    }

    /**
     * Причина, по которой Media не может быть мигрирована (null — может).
     * Только локальные проверки и наличие локального файла, без обращений к Диску.
     */
    public function eligibilityIssue(Media $media): ?string
    {
        $disk = (string) $media->disk;

        if ($disk === '') {
            return 'диск не указан';
        }

        if ($disk === self::TARGET_DISK) {
            return 'уже на Яндекс.Диске';
        }

        if ($media->isRemoteDisk($disk)) {
            return 'уже на удалённом диске ('.$disk.')';
        }

        if (in_array($disk, self::DERIVATIVE_DISKS, true)) {
            return 'производные (thumbnail/кэш) не мигрируются';
        }

        if (config("filesystems.disks.{$disk}") === null) {
            return 'неизвестный диск ('.$disk.')';
        }

        if ((string) config("filesystems.disks.{$disk}.driver") !== 'local') {
            return 'неподдерживаемый диск-источник ('.$disk.')';
        }

        if (blank($media->file_path)) {
            return 'путь к файлу не указан';
        }

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return 'не изображение (mime: '.($media->mime_type ?? 'неизвестно').')';
        }

        if (! Storage::disk($disk)->exists((string) $media->file_path)) {
            return 'локальный оригинал отсутствует';
        }

        return null;
    }

    /**
     * Конфликт с уже существующим удалённым файлом: путь занят файлом другого размера.
     * Используется в dry-run; полная проверка содержимого выполняется при миграции.
     */
    public function remoteConflictReason(Media $media): ?string
    {
        try {
            $target = $this->targetDisk();
            $path = (string) $media->file_path;

            if (! $target->exists($path)) {
                return null;
            }

            if ((int) $target->size($path) === (int) Storage::disk((string) $media->disk)->size($path)) {
                return null;
            }

            return 'на Яндекс.Диске уже есть файл другого размера';
        } catch (Throwable $exception) {
            return 'ошибка доступа к Яндекс.Диску: '.$exception->getMessage();
        }
    }

    protected function run(Media $media): MediaMigrationResult
    {
        if ($reason = $this->eligibilityIssue($media)) {
            return new MediaMigrationResult(MediaMigrationResult::SKIPPED, $reason);
        }

        $sourceDisk = Storage::disk((string) $media->disk);
        $targetDisk = $this->targetDisk();
        $path = (string) $media->file_path;

        $tempFile = $this->spoolToTempFile($sourceDisk, $path);

        if ($tempFile === null) {
            return new MediaMigrationResult(MediaMigrationResult::FAILED, 'локальный оригинал не читается');
        }

        try {
            $hash = (string) hash_file('sha256', $tempFile);
            $size = (int) filesize($tempFile);

            if ($targetDisk->exists($path)) {
                if (! $this->remoteMatches($targetDisk, $path, $size, $hash)) {
                    return new MediaMigrationResult(
                        MediaMigrationResult::FAILED,
                        'на Яндекс.Диске уже существует файл с другим содержимым',
                    );
                }

                $reused = true;
            } else {
                $reused = false;

                $uploadError = $this->upload($targetDisk, $path, $tempFile, (int) $media->id);

                if ($uploadError !== null) {
                    return new MediaMigrationResult(MediaMigrationResult::FAILED, $uploadError);
                }

                if (! $this->remoteMatches($targetDisk, $path, $size, $hash)) {
                    $this->removeBrokenUpload($targetDisk, $path);

                    return new MediaMigrationResult(MediaMigrationResult::FAILED, 'проверка файла после загрузки не пройдена');
                }
            }
        } finally {
            @unlink($tempFile);
        }

        $this->forgetStaleImageCache($media);

        $media->disk = self::TARGET_DISK;
        $media->save();

        $localDeleted = $this->deleteLocalOriginal($sourceDisk, $path);

        Log::info('Media original migrated to Yandex Disk.', [
            'media_id' => $media->id,
            'path' => $path,
            'remote_reused' => $reused ?? false,
            'local_deleted' => $localDeleted,
        ]);

        return new MediaMigrationResult(MediaMigrationResult::MIGRATED, localDeleted: $localDeleted);
    }

    /**
     * Загрузка во временную копию на Диске. Строковый ответ — описание ошибки.
     */
    protected function upload(Filesystem $targetDisk, string $path, string $tempFile, int $mediaId): ?string
    {
        try {
            $directory = dirname($path);

            if ($directory !== '.' && ! $targetDisk->directoryExists($directory)) {
                $targetDisk->makeDirectory($directory);
            }

            $stream = fopen($tempFile, 'rb');

            if ($stream === false) {
                return 'не удалось открыть временный файл для загрузки';
            }

            if (! $targetDisk->put($path, $stream)) {
                return 'не удалось загрузить файл на Яндекс.Диск';
            }

            return null;
        } catch (Throwable $exception) {
            Log::error('Yandex Disk upload failed.', [
                'media_id' => $mediaId,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return 'ошибка загрузки на Яндекс.Диск: '.$exception->getMessage();
        }
    }

    /**
     * Полная проверка удалённого файла: наличие, размер, содержимое (sha256).
     */
    protected function remoteMatches(Filesystem $targetDisk, string $path, int $expectedSize, string $expectedHash): bool
    {
        try {
            if ((int) $targetDisk->size($path) !== $expectedSize) {
                return false;
            }

            $tempFile = $this->spoolToTempFile($targetDisk, $path);

            if ($tempFile === null) {
                return false;
            }

            try {
                return hash_equals($expectedHash, (string) hash_file('sha256', $tempFile));
            } finally {
                @unlink($tempFile);
            }
        } catch (Throwable $exception) {
            Log::warning('Remote file verification failed.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Неудачная загрузка удаляется, чтобы повторный запуск мог повторить её начисто.
     */
    protected function removeBrokenUpload(Filesystem $targetDisk, string $path): void
    {
        try {
            $targetDisk->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Unable to remove broken remote upload.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Ключ кэша display/lightbox включает disk: после смены диска старые
     * варианты недостижимы. Удаляем их до обновления записи (best-effort).
     * Новые варианты генерируются лениво или через «Повторить обработку».
     */
    protected function forgetStaleImageCache(Media $media): void
    {
        try {
            $this->imageCache->forget($media);
        } catch (Throwable $exception) {
            Log::warning('Unable to forget stale image cache variants.', [
                'media_id' => $media->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Локальный оригинал удаляется только после проверки Диска и обновления БД.
     * Сбой удаления не откатывает миграцию: запись уже указывает на Диск,
     * оставшийся локальный файл — безвредный дубликат (orphan).
     */
    protected function deleteLocalOriginal(Filesystem $sourceDisk, string $path): bool
    {
        try {
            return (bool) $sourceDisk->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Local original left in place after migration.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function targetDisk(): Filesystem
    {
        return Storage::disk(self::TARGET_DISK);
    }

    protected function spoolToTempFile(Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'migrate-');

        if ($tempFile === false) {
            fclose($stream);

            return null;
        }

        $target = fopen($tempFile, 'wb');

        if ($target === false) {
            fclose($stream);
            @unlink($tempFile);

            return null;
        }

        stream_copy_to_stream($stream, $target);
        fclose($target);
        fclose($stream);

        return $tempFile;
    }
}
