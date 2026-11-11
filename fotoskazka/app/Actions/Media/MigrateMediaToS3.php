<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Миграция оригинала Media на S3-совместимый диск.
 *
 * Порядок операций: upload → verify (size + sha256) → DB update → delete source.
 * До успешной проверки удалённого файла и обновления записи БД исходный
 * оригинал гарантированно сохраняется.
 *
 * Поддерживаются любые типы файлов (изображения и видео). Для изображений
 * при миграции уточняются mime_type, width и height — это нужно для записей
 * без метаданных. Thumbnails и кэш производных остаются локальными.
 */
class MigrateMediaToS3
{
    public const TARGET_DISK = 's3';

    protected const DERIVATIVE_DISKS = ['thumbnails', 'image_cache'];

    public function __construct(protected ImageCacheService $imageCache) {}

    public function execute(Media $media, bool $deleteSource = true): MediaMigrationResult
    {
        try {
            return $this->run($media, $deleteSource);
        } catch (Throwable $exception) {
            Log::error('Media migration to S3 failed unexpectedly.', [
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
     */
    public function eligibilityIssue(Media $media): ?string
    {
        $disk = (string) $media->disk;

        if ($disk === '') {
            return 'диск не указан';
        }

        if ($disk === self::TARGET_DISK) {
            return 'уже на S3';
        }

        if (in_array($disk, self::DERIVATIVE_DISKS, true)) {
            return 'производные (thumbnail/кэш) не мигрируются';
        }

        if (config("filesystems.disks.{$disk}") === null) {
            return 'неизвестный диск ('.$disk.')';
        }

        if (blank($media->file_path)) {
            return 'путь к файлу не указан';
        }

        try {
            if (! Storage::disk($disk)->exists((string) $media->file_path)) {
                return 'оригинал отсутствует';
            }
        } catch (Throwable $exception) {
            return 'ошибка доступа к диску: '.$exception->getMessage();
        }

        return null;
    }

    /**
     * Конфликт с уже существующим файлом на S3 в dry-run.
     */
    public function remoteConflictReason(Media $media): ?string
    {
        try {
            $target = Storage::disk(self::TARGET_DISK);
            $path = (string) $media->file_path;

            if (! $target->exists($path)) {
                return null;
            }

            if ((int) $target->size($path) === (int) Storage::disk((string) $media->disk)->size($path)) {
                return null;
            }

            return 'на S3 уже есть файл другого размера';
        } catch (Throwable $exception) {
            return 'ошибка доступа к S3: '.$exception->getMessage();
        }
    }

    protected function run(Media $media, bool $deleteSource): MediaMigrationResult
    {
        if ($reason = $this->eligibilityIssue($media)) {
            return new MediaMigrationResult(MediaMigrationResult::SKIPPED, $reason);
        }

        $sourceDisk = Storage::disk((string) $media->disk);
        $targetDisk = Storage::disk(self::TARGET_DISK);
        $path = (string) $media->file_path;

        $tempFile = $this->spoolToTempFile($sourceDisk, $path);

        if ($tempFile === null) {
            return new MediaMigrationResult(MediaMigrationResult::FAILED, 'не удалось прочитать оригинал');
        }

        $reused = false;

        try {
            $size = (int) filesize($tempFile);
            $hash = (string) hash_file('sha256', $tempFile);
            $mime = $this->detectMime($tempFile, $media);
            [$width, $height] = $this->imageDimensions($tempFile, $mime);

            if ($targetDisk->exists($path)) {
                if (! $this->remoteMatches($targetDisk, $path, $size, $hash)) {
                    return new MediaMigrationResult(
                        MediaMigrationResult::FAILED,
                        'на S3 уже существует файл с другим содержимым',
                    );
                }

                $reused = true;
            } else {
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

        if ($mime !== null && $mime !== '') {
            $media->mime_type = $mime;
        }

        $media->file_size = $size;

        if ($width !== null && $height !== null) {
            $media->width = $media->width ?: $width;
            $media->height = $media->height ?: $height;
        }

        try {
            $media->save();
        } catch (Throwable $exception) {
            Log::error('Unable to update Media after S3 upload.', [
                'media_id' => $media->id,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return new MediaMigrationResult(MediaMigrationResult::FAILED, 'не удалось обновить запись Media');
        }

        $sourceDeleted = $deleteSource ? $this->deleteSource($sourceDisk, $path) : false;

        Log::info('Media original migrated to S3.', [
            'media_id' => $media->id,
            'path' => $path,
            'remote_reused' => $reused,
            'source_deleted' => $sourceDeleted,
        ]);

        return new MediaMigrationResult(MediaMigrationResult::MIGRATED, localDeleted: $sourceDeleted);
    }

    protected function upload(Filesystem $targetDisk, string $path, string $tempFile, int $mediaId): ?string
    {
        try {
            $stream = fopen($tempFile, 'rb');

            if ($stream === false) {
                return 'не удалось открыть временный файл для загрузки';
            }

            try {
                $written = $targetDisk->writeStream($path, $stream, ['visibility' => 'public']);
            } finally {
                fclose($stream);
            }

            if (! $written) {
                return 'не удалось загрузить файл на S3';
            }

            return null;
        } catch (Throwable $exception) {
            Log::error('S3 upload failed.', [
                'media_id' => $mediaId,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return 'ошибка загрузки на S3: '.$exception->getMessage();
        }
    }

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
            Log::warning('S3 file verification failed.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function removeBrokenUpload(Filesystem $targetDisk, string $path): void
    {
        try {
            $targetDisk->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Unable to remove broken S3 upload.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }
    }

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

    protected function deleteSource(Filesystem $sourceDisk, string $path): bool
    {
        try {
            return (bool) $sourceDisk->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Source original left in place after S3 migration.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function detectMime(string $tempFile, Media $media): ?string
    {
        $detected = @mime_content_type($tempFile);

        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        return $media->mime_type ?: null;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    protected function imageDimensions(string $tempFile, ?string $mime): array
    {
        if ($mime === null || ! str_starts_with($mime, 'image/')) {
            return [null, null];
        }

        $info = @getimagesize($tempFile);

        if (! is_array($info)) {
            return [null, null];
        }

        return [(int) $info[0], (int) $info[1]];
    }

    protected function spoolToTempFile(Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 's3-migrate-');

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

        try {
            if (stream_copy_to_stream($stream, $target) === false) {
                @unlink($tempFile);

                return null;
            }
        } finally {
            fclose($stream);
            fclose($target);
        }

        return $tempFile;
    }
}
