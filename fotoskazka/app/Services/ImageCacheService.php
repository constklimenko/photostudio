<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageCacheService
{
    public const TIER_DISPLAY = 'display';

    public const TIER_LIGHTBOX = 'lightbox';

    public function url(Media $media, string $tier): ?string
    {
        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return null;
        }

        return route($tier === self::TIER_DISPLAY ? 'media.display' : 'media.lightbox', ['media' => $media->getKey()]);
    }

    public function relativePath(Media $media, string $tier): string
    {
        $hash = substr(sha1($media->getKey().'|'.$tier.'|'.$media->disk.'|'.$media->file_path), 0, 12);

        return $tier.'/'.$media->getKey().'-'.$hash.'.png';
    }

    /**
     * Возвращает путь к кэшированной версии, генерируя её при отсутствии.
     */
    public function ensureCached(Media $media, string $tier): ?string
    {
        if (! array_key_exists($tier, $this->tiers())) {
            return null;
        }

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return null;
        }

        $path = $this->relativePath($media, $tier);
        $disk = $this->cacheDisk();

        if ($disk->exists($path)) {
            return $path;
        }

        return $this->generate($media, $tier, $path) ? $path : null;
    }

    public function isCached(Media $media, string $tier): bool
    {
        if (! array_key_exists($tier, $this->tiers())) {
            return false;
        }

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return false;
        }

        return $this->cacheDisk()->exists($this->relativePath($media, $tier));
    }

    /**
     * Прогрев кэша из уже скачанного локального файла оригинала
     * (без повторного скачивания с удалённого диска).
     */
    public function warmCached(Media $media, string $tier, string $tempFile, bool $force = false): bool
    {
        if (! array_key_exists($tier, $this->tiers())) {
            return false;
        }

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return false;
        }

        $path = $this->relativePath($media, $tier);

        if (! $force && $this->cacheDisk()->exists($path)) {
            return true;
        }

        if (! $this->generateFromTempFile($media, $tier, $tempFile, $path)) {
            return false;
        }

        $this->purgeSafely();

        return true;
    }

    public function generate(Media $media, string $tier, ?string $path = null): bool
    {
        $sourceDisk = Storage::disk($media->disk ?? 'public');

        if (! $media->file_path || ! $sourceDisk->exists($media->file_path)) {
            return false;
        }

        $tempFile = $this->spoolToTempFile($sourceDisk, (string) $media->file_path);

        if ($tempFile === null) {
            return false;
        }

        try {
            if (! $this->generateFromTempFile($media, $tier, $tempFile, $path)) {
                return false;
            }
        } finally {
            @unlink($tempFile);
        }

        $this->purgeSafely();

        return true;
    }

    /**
     * LRU-обрезка не критична: сбой листинга/удаления не должен
     * помечать генерацию варианта как неудачную.
     */
    protected function purgeSafely(): void
    {
        try {
            $this->purgeToLimit();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function generateFromTempFile(Media $media, string $tier, string $tempFile, ?string $path): bool
    {
        $srcImage = $this->loadImage((string) $media->mime_type, $tempFile);

        if ($srcImage === false || $srcImage === null) {
            return false;
        }

        try {
            $width = imagesx($srcImage);
            $height = imagesy($srcImage);
            $maxSide = $this->maxSideFor($tier);

            if ($width <= $maxSide && $height <= $maxSide) {
                $newWidth = $width;
                $newHeight = $height;
            } elseif ($width >= $height) {
                $newWidth = $maxSide;
                $newHeight = (int) round($height * $maxSide / $width);
            } else {
                $newHeight = $maxSide;
                $newWidth = (int) round($width * $maxSide / $height);
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($srcImage);

            $stream = fopen('php://temp', 'w+');
            imagepng($resized, $stream, (int) config('filesystems.image_cache.png_level', 6));
            imagedestroy($resized);

            rewind($stream);

            $written = $this->cacheDisk()->put($path ?? $this->relativePath($media, $tier), $stream);
            fclose($stream);

            return (bool) $written;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Удерживает размер кэша в пределах лимита, удаляя самые старые файлы.
     * Возвращает освобождённый объём в байтах.
     */
    public function purgeToLimit(): int
    {
        $limitBytes = (int) floor(max(0.0, (float) config('filesystems.image_cache.max_size_mb', 2048)) * 1024 * 1024);

        $disk = $this->cacheDisk();

        $files = [];

        foreach ($disk->allFiles() as $file) {
            try {
                $files[] = [
                    'path' => $file,
                    'size' => $disk->size($file),
                    'mtime' => $disk->lastModified($file),
                ];
            } catch (Throwable) {
                continue;
            }
        }

        $total = array_sum(array_column($files, 'size'));

        if ($total <= $limitBytes) {
            return 0;
        }

        usort($files, fn (array $a, array $b): int => $a['mtime'] <=> $b['mtime']);

        $freed = 0;

        foreach ($files as $file) {
            if ($total - $freed <= $limitBytes) {
                break;
            }

            $disk->delete($file['path']);
            $freed += $file['size'];
        }

        return $freed;
    }

    public function totalSize(): int
    {
        $disk = $this->cacheDisk();
        $total = 0;

        foreach ($disk->allFiles() as $file) {
            try {
                $total += $disk->size($file);
            } catch (Throwable) {
                continue;
            }
        }

        return $total;
    }

    public function clear(): void
    {
        foreach ($this->cacheDisk()->allDirectories() as $directory) {
            $this->cacheDisk()->deleteDirectory($directory);
        }
    }

    public function tiers(): array
    {
        return (array) config('filesystems.image_cache.tiers', [
            self::TIER_DISPLAY => 800,
            self::TIER_LIGHTBOX => 1600,
        ]);
    }

    protected function cacheDisk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.image_cache.disk', 'image_cache'));
    }

    protected function maxSideFor(string $tier): int
    {
        return (int) ($this->tiers()[$tier] ?? 1600);
    }

    protected function loadImage(string $mime, string $tempFile)
    {
        return match (true) {
            in_array($mime, ['image/jpeg', 'image/jpg'], true) => imagecreatefromjpeg($tempFile),
            $mime === 'image/png' => imagecreatefrompng($tempFile),
            $mime === 'image/webp' => imagecreatefromwebp($tempFile),
            $mime === 'image/gif' => imagecreatefromgif($tempFile),
            default => null,
        };
    }

    /**
     * Скачивает файл с диска во временный файл на локальной ФС.
     *
     * readStream у удалённых дисков (Яндекс.Диск) возвращает сетевой поток,
     * чей URI не является путём к локальному файлу — GD работает только
     * с настоящими файлами.
     */
    protected function spoolToTempFile(Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'imgcache-');

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
