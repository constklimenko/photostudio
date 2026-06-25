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
        $path = $media->file_path;
        $disk = $media->disk ?? 'public';

        if (! Storage::disk($disk)->exists($path)) {
            return;
        }

        $fullPath = Storage::disk($disk)->path($path);

        $media->mime_type = mime_content_type($fullPath) ?: null;
        $media->file_size = filesize($fullPath) ?: null;

        if (str_starts_with($media->mime_type ?? '', 'image/')) {
            $imageInfo = getimagesize($fullPath);
            if ($imageInfo) {
                $media->width = $imageInfo[0];
                $media->height = $imageInfo[1];
            }
        }
    }

    protected function generateThumbnail(Media $media): void
    {
        $path = $media->file_path;
        $disk = $media->disk ?? 'public';

        if (! Storage::disk($disk)->exists($path)) {
            return;
        }

        $fullPath = Storage::disk($disk)->path($path);

        if (! str_starts_with($media->mime_type ?? '', 'image/')) {
            return;
        }

        $info = pathinfo($path);
        $thumbDir = 'thumbnails/'.($info['dirname'] ?? '');
        $thumbPath = $thumbDir.'/'.$info['filename'].'_thumb.webp';

        if (! Storage::disk($disk)->exists($thumbDir)) {
            Storage::disk($disk)->makeDirectory($thumbDir);
        }

        $thumbFullPath = Storage::disk($disk)->path($thumbPath);

        $imageInfo = getimagesize($fullPath);
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
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($fullPath),
            'image/png' => imagecreatefrompng($fullPath),
            'image/webp' => imagecreatefromwebp($fullPath),
            'image/gif' => imagecreatefromgif($fullPath),
            default => null,
        };

        if (! $srcImage) {
            return;
        }

        $thumbImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        imagewebp($thumbImage, $thumbFullPath, 80);
        imagedestroy($srcImage);
        imagedestroy($thumbImage);

        $media->thumbnail_path = $thumbPath;
    }
}
