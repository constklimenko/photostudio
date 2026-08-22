<?php

namespace App\Observers;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class MediaObserver
{
    public function creating(Media $media): void
    {
        $media->disk = $media->disk ?? 'public';

        $path = (string) $media->file_path;
        $disk = Storage::disk($media->disk);

        if ($path === '' || ! $disk->exists($path)) {
            return;
        }

        $media->file_size = $disk->size($path);

        $tempFile = $this->spoolToTempFile($disk, $path);

        if ($tempFile === null) {
            return;
        }

        try {
            $media->mime_type = mime_content_type($tempFile) ?: null;

            if (! str_starts_with((string) $media->mime_type, 'image/')) {
                return;
            }

            $imageInfo = getimagesize($tempFile);

            if ($imageInfo === false) {
                return;
            }

            $media->width = $imageInfo[0];
            $media->height = $imageInfo[1];

            $this->writeThumbnail($media, $tempFile, $imageInfo[0], $imageInfo[1]);
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Скачивает файл с диска во временный файл на локальной ФС.
     *
     * readStream у удалённых дисков (Яндекс.Диск) возвращает сетевой поток,
     * чей URI не является путём к локальному файлу — поэтому для
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

    protected function writeThumbnail(Media $media, string $tempFile, int $width, int $height): void
    {
        $maxSize = 400;

        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = (int) round($height * $maxSize / $width);
        } else {
            $newHeight = $maxSize;
            $newWidth = (int) round($width * $maxSize / $height);
        }

        $srcImage = match ((string) $media->mime_type) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($tempFile),
            'image/png' => imagecreatefrompng($tempFile),
            'image/webp' => imagecreatefromwebp($tempFile),
            'image/gif' => imagecreatefromgif($tempFile),
            default => null,
        };

        if ($srcImage === false || $srcImage === null) {
            return;
        }

        $thumbImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $info = pathinfo((string) $media->file_path);
        $thumbDir = ($info['dirname'] ?? '') !== '.' ? ($info['dirname'] ?? '') : '';
        // Avoid duplicating 'thumbnails' directory since we're already on the thumbnails disk
        $thumbDir = ltrim($thumbDir, 'thumbnails/');
        $thumbDir = ltrim($thumbDir, '/');
        $thumbFileName = $info['filename'].'_thumb.webp';
        $thumbPath = $thumbDir ? $thumbDir.'/'.$thumbFileName : $thumbFileName;

        $thumbStream = fopen('php://temp', 'w+');
        imagewebp($thumbImage, $thumbStream, 80);
        imagedestroy($srcImage);
        imagedestroy($thumbImage);

        rewind($thumbStream);
        Storage::disk('thumbnails')->put($thumbPath, $thumbStream);
        fclose($thumbStream);

        $media->thumbnail_path = $thumbPath;
    }
}
