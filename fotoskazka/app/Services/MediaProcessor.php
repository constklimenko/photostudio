<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaProcessor
{
    public const THUMBNAIL_MAX_SIZE = 400;

    public const THUMBNAIL_QUALITY = 80;

    public function __construct(protected ImageCacheService $imageCache = new ImageCacheService) {}

    /**
     * Централизованная обработка Media: метаданные оригинала + WebP-thumbnail.
     *
     * Повторный вызов безопасен: заполняются только пустые поля,
     * существующий thumbnail не пересоздаётся (кроме force = true).
     * Ошибки логируются с контекстом и не приводят к потере данных:
     * запись Media остаётся пригодной для повторной обработки.
     */
    public function process(Media $media, bool $force = false): bool
    {
        try {
            return $this->handle($media, $force);
        } catch (Throwable $exception) {
            $this->reportFailure($media, $exception);

            return false;
        }
    }

    /**
     * Аналог process(), но сбой storage (Throwable) пробрасывается после логирования —
     * для Queue Job, где временные ошибки должны приводить к retry.
     */
    public function processOrFail(Media $media, bool $force = false): bool
    {
        try {
            return $this->handle($media, $force);
        } catch (Throwable $exception) {
            $this->reportFailure($media, $exception);

            throw $exception;
        }
    }

    protected function reportFailure(Media $media, Throwable $exception): void
    {
        Log::error('Media processing failed.', [
            'media_id' => $media->id,
            'disk' => $media->disk,
            'path' => $media->file_path,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Детерминированный путь thumbnail на диске `thumbnails`
     * относительно исходного пути оригинала.
     */
    public function thumbnailPath(string $originalPath): string
    {
        $info = pathinfo($originalPath);
        $directory = (($info['dirname'] ?? '') !== '.')
            ? trim((string) ($info['dirname'] ?? ''), '/')
            : '';
        $filename = ($info['filename'] ?? 'file').'_thumb.webp';

        return $directory === '' ? $filename : $directory.'/'.$filename;
    }

    protected function handle(Media $media, bool $force): bool
    {
        $path = (string) $media->file_path;

        if ($path === '') {
            Log::warning('Media has no file_path, nothing to process.', [
                'media_id' => $media->id,
            ]);

            return false;
        }

        if (! $this->needsProcessing($media, $force)) {
            return true;
        }

        $originalDisk = $this->originalDisk($media);

        if (! $originalDisk->exists($path)) {
            Log::warning('Media original file not found on disk.', [
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => $path,
            ]);

            return false;
        }

        $tempFile = $this->spoolToTempFile($originalDisk, $path);

        if ($tempFile === null) {
            Log::warning('Cannot read media original from disk.', [
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => $path,
            ]);

            return false;
        }

        try {
            return $this->processFromTempFile($media, $tempFile, $force);
        } finally {
            @unlink($tempFile);
        }
    }

    protected function processFromTempFile(Media $media, string $tempFile, bool $force): bool
    {
        $ok = true;
        $context = [
            'media_id' => $media->id,
            'disk' => $media->disk,
            'path' => (string) $media->file_path,
        ];

        if (blank($media->mime_type)) {
            $detected = mime_content_type($tempFile) ?: null;

            if ($detected === null) {
                Log::warning('Unable to detect media MIME type.', $context);

                $ok = false;
            } else {
                $media->mime_type = $detected;
            }
        }

        if (blank($media->file_size)) {
            $size = filesize($tempFile);

            if ($size !== false) {
                $media->file_size = $size;
            }
        }

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            $this->persistIfDirty($media);

            return $ok;
        }

        if (blank($media->width) || blank($media->height)) {
            $dimensions = @getimagesize($tempFile);

            if ($dimensions === false) {
                Log::warning('Image appears to be corrupted: dimensions not readable.', $context);

                $this->persistIfDirty($media);

                return false;
            }

            $media->width = (int) $dimensions[0];
            $media->height = (int) $dimensions[1];
        }

        if ($force || blank($media->thumbnail_path) || ! $this->thumbnailDisk()->exists((string) $media->thumbnail_path)) {
            try {
                if (! $this->writeThumbnail($media, $tempFile)) {
                    $ok = false;
                }
            } catch (Throwable $exception) {
                Log::warning('Unable to create thumbnail.', $context + ['error' => $exception->getMessage()]);

                $ok = false;
            }
        }

        if (! $this->warmImageCache($media, $tempFile, $force, $context)) {
            $ok = false;
        }

        $this->persistIfDirty($media);

        return $ok;
    }

    /**
     * Обработка Media не завершена: не хватает метаданных, thumbnail
     * или одного из кэш-вариантов. Используется Filament-UX для показа
     * статуса и действия «Повторить обработку».
     */
    public function isPending(Media $media): bool
    {
        return $this->needsProcessing($media, force: false);
    }

    protected function needsProcessing(Media $media, bool $force): bool
    {
        if ($force) {
            return true;
        }

        $isImage = str_starts_with((string) $media->mime_type, 'image/');

        if (! $isImage) {
            return blank($media->mime_type)
                || blank($media->file_size);
        }

        $thumbnailMissing = blank($media->thumbnail_path)
            || ! $this->thumbnailDisk()->exists((string) $media->thumbnail_path);

        return blank($media->mime_type)
            || blank($media->file_size)
            || blank($media->width)
            || blank($media->height)
            || $thumbnailMissing
            || $this->imageCacheVariantsMissing($media);
    }

    /**
     * Прогрев display/lightbox из уже скачанного temp-файла оригинала.
     * Ошибка одного варианта не мешает остальным, но помечает обработку
     * как неполную — Queue Job повторит её при retry.
     */
    protected function warmImageCache(Media $media, string $tempFile, bool $force, array $context): bool
    {
        $ok = true;

        foreach (array_keys($this->imageCache->tiers()) as $tier) {
            try {
                if (! $this->imageCache->warmCached($media, (string) $tier, $tempFile, $force)) {
                    Log::warning('Unable to warm image cache variant.', $context + ['tier' => $tier]);

                    $ok = false;
                }
            } catch (Throwable $exception) {
                Log::warning('Unable to warm image cache variant.', $context + [
                    'tier' => $tier,
                    'error' => $exception->getMessage(),
                ]);

                $ok = false;
            }
        }

        return $ok;
    }

    protected function imageCacheVariantsMissing(Media $media): bool
    {
        foreach (array_keys($this->imageCache->tiers()) as $tier) {
            if (! $this->imageCache->isCached($media, (string) $tier)) {
                return true;
            }
        }

        return false;
    }

    protected function writeThumbnail(Media $media, string $tempFile): bool
    {
        $context = [
            'media_id' => $media->id,
            'disk' => $media->disk,
            'path' => (string) $media->file_path,
        ];

        try {
            $source = $this->loadImage((string) $media->mime_type, $tempFile);
        } catch (Throwable) {
            $source = false;
        }

        if ($source === false || $source === null) {
            Log::warning('Unable to decode image for thumbnail.', $context);

            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxSize = self::THUMBNAIL_MAX_SIZE;

        if ($width >= $height) {
            $newWidth = min($width, $maxSize);
            $newHeight = (int) round($height * $newWidth / $width);
        } else {
            $newHeight = min($height, $maxSize);
            $newWidth = (int) round($width * $newHeight / $height);
        }

        $thumbnail = imagecreatetruecolor(max(1, $newWidth), max(1, $newHeight));
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        $stream = fopen('php://temp', 'w+');
        $encoded = imagewebp($thumbnail, $stream, self::THUMBNAIL_QUALITY);
        imagedestroy($thumbnail);

        if (! $encoded) {
            fclose($stream);
            Log::warning('Unable to encode WebP thumbnail.', $context);

            return false;
        }

        rewind($stream);

        $thumbPath = $this->thumbnailPath((string) $media->file_path);
        $written = $this->thumbnailDisk()->put($thumbPath, $stream);
        fclose($stream);

        if (! $written) {
            Log::warning('Unable to write thumbnail to thumbnails disk.', $context + ['thumbnail_path' => $thumbPath]);

            return false;
        }

        $media->thumbnail_path = $thumbPath;

        return true;
    }

    protected function loadImage(string $mimeType, string $tempFile)
    {
        return match (true) {
            in_array($mimeType, ['image/jpeg', 'image/jpg'], true) => @imagecreatefromjpeg($tempFile),
            $mimeType === 'image/png' => @imagecreatefrompng($tempFile),
            $mimeType === 'image/webp' => @imagecreatefromwebp($tempFile),
            $mimeType === 'image/gif' => @imagecreatefromgif($tempFile),
            default => null,
        };
    }

    protected function persistIfDirty(Media $media): void
    {
        if ($media->isDirty()) {
            $media->save();
        }
    }

    protected function originalDisk(Media $media): Filesystem
    {
        return Storage::disk($media->disk ?? (string) config('filesystems.default_media_disk', 'public'));
    }

    protected function thumbnailDisk(): Filesystem
    {
        return Storage::disk('thumbnails');
    }

    /**
     * Скачивает файл с диска во временный файл на локальной ФС.
     *
     * readStream у удалённых дисков (Яндекс.Диск) возвращает сетевой поток,
     * чей URI не является путём к локальному файлу — поэтому
     * mime_content_type / getimagesize / GD нужен настоящий файл.
     */
    protected function spoolToTempFile(Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'media-');

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
