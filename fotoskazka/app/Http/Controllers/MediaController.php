<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function original(Media $media): StreamedResponse
    {
        $disk = Storage::disk($media->disk ?? 'public');

        abort_unless($media->file_path && $disk->exists($media->file_path), 404);

        return $this->stream(
            $disk,
            (string) $media->file_path,
            $media->mime_type ?: 'application/octet-stream',
            'inline',
            basename((string) $media->file_path),
        );
    }

    public function download(Media $media): StreamedResponse
    {
        $disk = Storage::disk($media->disk ?? 'public');

        abort_unless($media->file_path && $disk->exists($media->file_path), 404);

        return $this->stream(
            $disk,
            (string) $media->file_path,
            $media->mime_type ?: 'application/octet-stream',
            'attachment',
            basename((string) $media->file_path),
        );
    }

    public function display(Media $media, ImageCacheService $cache): StreamedResponse
    {
        return $this->cachedImage($media, $cache, ImageCacheService::TIER_DISPLAY);
    }

    public function lightbox(Media $media, ImageCacheService $cache): StreamedResponse
    {
        return $this->cachedImage($media, $cache, ImageCacheService::TIER_LIGHTBOX);
    }

    protected function cachedImage(Media $media, ImageCacheService $cache, string $tier): StreamedResponse
    {
        $path = $cache->ensureCached($media, $tier);

        abort_if($path === null, 404);

        $disk = Storage::disk((string) config('filesystems.image_cache.disk', 'image_cache'));

        return $this->stream($disk, $path, 'image/png', 'inline', basename($path), true);
    }

    protected function stream(
        Filesystem $disk,
        string $path,
        string $contentType,
        string $disposition,
        string $filename,
        bool $immutable = false,
    ): StreamedResponse {
        return response()->stream(
            function () use ($disk, $path): void {
                fpassthru($disk->readStream($path));
            },
            200,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => $disposition.'; filename="'.addslashes($filename).'"',
                'Cache-Control' => $immutable ? 'public, max-age=31536000, immutable' : 'public, max-age=86400',
            ],
        );
    }
}
