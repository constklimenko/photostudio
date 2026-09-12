<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\ImageCacheService;
use App\Services\MediaAccessService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function original(Media $media, MediaAccessService $access): StreamedResponse
    {
        $this->authorizeView($media, $access);

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

    public function thumbnail(Media $media, MediaAccessService $access): StreamedResponse
    {
        $this->authorizeView($media, $access);

        $disk = Storage::disk('thumbnails');

        abort_unless($media->thumbnail_path && $disk->exists($media->thumbnail_path), 404);

        return $this->stream(
            $disk,
            (string) $media->thumbnail_path,
            'image/webp',
            'inline',
            basename((string) $media->thumbnail_path),
        );
    }

    public function download(Media $media, MediaAccessService $access): StreamedResponse
    {
        $this->authorizeView($media, $access);

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

    public function display(Media $media, ImageCacheService $cache, MediaAccessService $access): StreamedResponse
    {
        $this->authorizeView($media, $access);

        return $this->cachedImage($media, $cache, ImageCacheService::TIER_DISPLAY);
    }

    public function lightbox(Media $media, ImageCacheService $cache, MediaAccessService $access): StreamedResponse
    {
        $this->authorizeView($media, $access);

        return $this->cachedImage($media, $cache, ImageCacheService::TIER_LIGHTBOX);
    }

    protected function authorizeView(Media $media, MediaAccessService $access): void
    {
        if ($access->isPublic($media)) {
            return;
        }

        if (auth()->guest()) {
            abort(404);
        }

        abort_unless($access->canView($media, auth()->user()), 403);
    }

    protected function cachedImage(Media $media, ImageCacheService $cache, string $tier): StreamedResponse
    {
        $path = $cache->ensureCached($media, $tier);

        abort_if($path === null, 404);

        $disk = Storage::disk((string) config('filesystems.image_cache.disk', 'image_cache'));

        return $this->stream($disk, $path, $cache->mimeType($tier), 'inline', basename($path), true);
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
