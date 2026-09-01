<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Services\ImageCacheService;
use App\Services\MediaProcessor;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RotateMedia
{
    public function __construct(
        protected MediaProcessor $processor,
        protected ImageCacheService $imageCache,
    ) {}

    /**
     * Поворачивает оригинал изображения по часовой стрелке на указанное
     * число градусов (кратно 90) и пересобирает все производные:
     * WebP-thumbnail, display/lightbox-кэш и метаданные.
     *
     * Оригинал перезаписывается на том же диске через Laravel Filesystem,
     * поэтому повторный вызов выполняет ещё один поворот. Повторная обработка
     * производных идемпотентна и безопасна (MediaProcessor).
     */
    public function execute(Media $media, int $degrees): bool
    {
        $degrees = ((int) $degrees % 360 + 360) % 360;

        if ($degrees === 0 || $degrees % 90 !== 0) {
            return false;
        }

        $disk = $this->originalDisk($media);
        $path = (string) $media->file_path;

        if ($path === '' || ! $disk->exists($path)) {
            Log::warning('Cannot rotate media: original file not found.', [
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => $path,
            ]);

            return false;
        }

        $tempFile = $this->spoolToTempFile($disk, $path);

        if ($tempFile === null) {
            return false;
        }

        $output = null;

        try {
            $output = $this->rotateFile($tempFile, $degrees);
        } catch (Throwable $exception) {
            Log::error('Media rotation failed.', [
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => $path,
                'degrees' => $degrees,
                'error' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            @unlink($tempFile);
        }

        if ($output === null) {
            return false;
        }

        try {
            return $this->commitRotation($media, $disk, $path, $output);
        } finally {
            @unlink($output);
        }
    }

    protected function rotateFile(string $tempFile, int $degrees): ?string
    {
        $mime = mime_content_type($tempFile) ?: 'image/jpeg';

        $source = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($tempFile),
            'image/png' => imagecreatefrompng($tempFile),
            'image/webp' => imagecreatefromwebp($tempFile),
            default => false,
        };

        if ($source === false) {
            Log::warning('Unable to decode image for rotation.', [
                'mime' => $mime,
            ]);

            return null;
        }

        // imagerotate вращает против часовой стрелки — для поворота
        // по часовой передаём дополнение до 360°.
        $rotated = imagerotate($source, 360 - $degrees, 0);

        imagedestroy($source);

        if ($rotated === false) {
            Log::warning('GD failed to rotate image.', [
                'degrees' => $degrees,
            ]);

            return null;
        }

        $output = tempnam(sys_get_temp_dir(), 'rotate-');

        if ($output === false) {
            imagedestroy($rotated);

            return null;
        }

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagesavealpha($rotated, true);
        }

        $encoded = match ($mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($rotated, $output, 92),
            'image/png' => imagepng($rotated, $output, 6),
            'image/webp' => imagewebp($rotated, $output, 90),
            default => false,
        };

        imagedestroy($rotated);

        if (! $encoded) {
            @unlink($output);

            return null;
        }

        return $output;
    }

    protected function commitRotation(Media $media, Filesystem $disk, string $path, string $output): bool
    {
        $stream = fopen($output, 'rb');

        if ($stream === false) {
            return false;
        }

        $written = $disk->put($path, $stream);

        fclose($stream);

        if (! $written || ! $disk->exists($path)) {
            Log::error('Unable to write rotated original to storage.', [
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => $path,
            ]);

            return false;
        }

        $size = filesize($output);
        $dimensions = @getimagesize($output);

        $media->forceFill([
            'file_size' => $size !== false ? $size : $media->file_size,
            'width' => $dimensions[0] ?? $media->width,
            'height' => $dimensions[1] ?? $media->height,
        ])->save();

        $this->imageCache->forget($media);

        if (! $this->processor->process($media, force: true)) {
            Log::warning('Rotated original saved, but derivative regeneration failed.', [
                'media_id' => $media->id,
            ]);
        }

        return true;
    }

    protected function originalDisk(Media $media): Filesystem
    {
        return Storage::disk($media->disk ?? (string) config('filesystems.default_media_disk', 'public'));
    }

    protected function spoolToTempFile(Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'rotate-');

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
