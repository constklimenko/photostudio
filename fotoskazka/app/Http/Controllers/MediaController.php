<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\ImageCacheService;
use App\Services\MediaAccessService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
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

    public function thumbnail(Media $media, MediaAccessService $access): Response
    {
        $this->authorizeView($media, $access);

        $disk = Storage::disk('thumbnails');

        abort_unless($media->thumbnail_path && $disk->exists($media->thumbnail_path), 404);

        $path = (string) $media->thumbnail_path;

        if ($this->xAccelEnabled() && $disk->getAdapter() instanceof LocalFilesystemAdapter) {
            return $this->accel(
                (string) config('filesystems.thumbnail_x_accel_location', '/protected-thumbnails/'),
                $path,
                'image/webp',
                basename($path),
                false,
            );
        }

        return $this->stream(
            $disk,
            $path,
            'image/webp',
            'inline',
            basename($path),
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

    public function display(Media $media, ImageCacheService $cache, MediaAccessService $access): Response
    {
        $this->authorizeView($media, $access);

        return $this->cachedImage($media, $cache, ImageCacheService::TIER_DISPLAY);
    }

    public function lightbox(Media $media, ImageCacheService $cache, MediaAccessService $access): Response
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

    protected function cachedImage(Media $media, ImageCacheService $cache, string $tier): Response
    {
        $path = $cache->ensureCached($media, $tier);

        abort_if($path === null, 404);

        $disk = Storage::disk((string) config('filesystems.image_cache.disk', 'image_cache'));
        $contentType = $cache->mimeType($tier);

        if ($this->xAccelEnabled() && $disk->getAdapter() instanceof LocalFilesystemAdapter) {
            return $this->accel(
                (string) config('filesystems.image_x_accel_location', '/protected-images/'),
                $path,
                $contentType,
                basename($path),
                true,
            );
        }

        return $this->stream($disk, $path, $contentType, 'inline', basename($path), true);
    }

    protected function xAccelEnabled(): bool
    {
        return (bool) config('filesystems.image_x_accel', false);
    }

    protected function accel(
        string $location,
        string $path,
        string $contentType,
        string $filename,
        bool $immutable,
    ): Response {
        $prefix = rtrim($location, '/');
        $uri = $prefix.'/'.implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));

        return new Response('', 200, [
            'X-Accel-Redirect' => $uri,
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => $immutable ? 'public, max-age=31536000, immutable' : 'public, max-age=86400',
        ]);
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
