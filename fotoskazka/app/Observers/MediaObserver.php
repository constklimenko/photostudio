<?php

namespace App\Observers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaObserver
{
    public function creating(Media $media): void
    {
        $this->fillMetadata($media);
        $this->generateThumbnail($media);
    }

    protected function fillMetadata(Media $media): void
    {
        $media->disk = $media->disk ?? 'public';
        $path = $media->file_path;
        $disk = $media->disk;

        if (! Storage::disk($disk)->exists($path)) {
            return;
        }

        $media->file_size = Storage::disk($disk)->size($path);

        $stream = Storage::disk($disk)->readStream($path);
        if (! $stream) {
            return;
        }

        $meta = stream_get_meta_data($stream);
        $tempPath = $meta['uri'] ?? null;
        fclose($stream);

        if (! $tempPath) {
            return;
        }

        $media->mime_type = mime_content_type($tempPath) ?: null;

        if (str_starts_with($media->mime_type ?? '', 'image/')) {
            $imageInfo = getimagesize($tempPath);
            if ($imageInfo) {
                $media->width = $imageInfo[0];
                $media->height = $imageInfo[1];
            }
        }
    }

    protected function generateThumbnail(Media $media): void
    {
        $path = $media->file_path;
        $disk = $media->disk;

        if (! Storage::disk($disk)->exists($path)) {
            return;
        }

        if (! str_starts_with($media->mime_type ?? '', 'image/')) {
            return;
        }

        $stream = Storage::disk($disk)->readStream($path);
        if (! $stream) {
            return;
        }

        $meta = stream_get_meta_data($stream);
        $tempPath = $meta['uri'] ?? null;

        if (! $tempPath) {
            fclose($stream);

            return;
        }

        $imageInfo = getimagesize($tempPath);
        fclose($stream);

        if (! $imageInfo) {
            return;
        }

        [$width, $height] = $imageInfo;
        $maxSize = 400;

        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = (int) round($height * $maxSize / $width);
        } else {
            $newHeight = $maxSize;
            $newWidth = (int) round($width * $maxSize / $height);
        }

        $srcImage = match ($media->mime_type) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($tempPath),
            'image/png' => imagecreatefrompng($tempPath),
            'image/webp' => imagecreatefromwebp($tempPath),
            'image/gif' => imagecreatefromgif($tempPath),
            default => null,
        };

        if (! $srcImage) {
            return;
        }

        $thumbImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $info = pathinfo($path);
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
