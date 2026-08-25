<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CheckMediaIntegrity
{
    protected ?Media $media = null;

    public function __construct(protected ImageCacheService $imageCache) {}

    public function check(Media $media): MediaCheckResult
    {
        $this->media = $media;

        try {
            $result = $this->run();

            $this->media = null;

            return $result;
        } catch (Throwable $exception) {
            Log::error('Media integrity check failed.', [
                'media_id' => $media->id,
                'error' => $exception->getMessage(),
            ]);

            $this->media = null;

            return new MediaCheckResult(MediaCheckResult::ERROR, $exception->getMessage());
        }
    }

    protected function run(): MediaCheckResult
    {
        $media = $this->media;

        if (! $this->originalExists()) {
            return new MediaCheckResult(MediaCheckResult::MISSING_ORIGINAL);
        }

        $thumbnailMissing = $this->isImage() && ! $this->thumbnailExists();
        $cacheMissing = $this->isImage() && ! $this->imageCacheComplete();
        $metadataIssue = $this->metadataProblem();

        if ($thumbnailMissing) {
            return new MediaCheckResult(MediaCheckResult::MISSING_THUMBNAIL);
        }

        if ($cacheMissing) {
            return new MediaCheckResult(MediaCheckResult::MISSING_IMAGE_CACHE);
        }

        if ($metadataIssue !== null) {
            return new MediaCheckResult(MediaCheckResult::METADATA_MISMATCH, $metadataIssue);
        }

        return new MediaCheckResult(MediaCheckResult::VALID);
    }

    protected function originalExists(): bool
    {
        $media = $this->media;
        $disk = (string) $media->disk;

        if ($disk === '') {
            return false;
        }

        $path = (string) $media->file_path;

        if ($path === '') {
            return false;
        }

        try {
            return Storage::disk($disk)->exists($path);
        } catch (Throwable) {
            return false;
        }
    }

    protected function thumbnailExists(): bool
    {
        $media = $this->media;

        if (blank($media->thumbnail_path)) {
            return false;
        }

        try {
            return Storage::disk('thumbnails')->exists((string) $media->thumbnail_path);
        } catch (Throwable) {
            return false;
        }
    }

    protected function imageCacheComplete(): bool
    {
        $media = $this->media;

        foreach (array_keys($this->imageCache->tiers()) as $tier) {
            if (! $this->imageCache->isCached($media, (string) $tier)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if stored metadata is plausible for the file type.
     *
     * For images: dimensions should be present if the file was processed;
     * file_size should be populated.
     *
     * For local files: verify file_size matches the actual file.
     *
     * Returns null if no issue found, or a description of the problem.
     */
    protected function metadataProblem(): ?string
    {
        $media = $this->media;

        if (! $this->isImage()) {
            if (blank($media->file_size)) {
                return 'file_size is missing';
            }

            return null;
        }

        if (blank($media->file_size)) {
            return 'file_size is missing';
        }

        if (blank($media->width) || blank($media->height)) {
            return 'image dimensions are missing';
        }

        if ((int) $media->width <= 0 || (int) $media->height <= 0) {
            return 'invalid dimensions: '.$media->width.'x'.$media->height;
        }

        if ($this->isLocalDisk()) {
            $actualSize = $this->localFileSize();

            if ($actualSize !== null && (int) $media->file_size !== $actualSize) {
                return 'file_size mismatch: stored='.(int) $media->file_size.', actual='.$actualSize;
            }
        }

        return null;
    }

    protected function isImage(): bool
    {
        return str_starts_with((string) $this->media->mime_type, 'image/');
    }

    protected function isLocalDisk(): bool
    {
        $disk = (string) $this->media->disk;

        return $disk !== '' && ! $this->media->isRemoteDisk($disk);
    }

    protected function localFileSize(): ?int
    {
        $media = $this->media;
        $disk = (string) $media->disk;

        if ($disk === '') {
            return null;
        }

        $path = (string) $media->file_path;

        if ($path === '') {
            return null;
        }

        try {
            $stream = Storage::disk($disk)->readStream($path);

            if (! is_resource($stream)) {
                return null;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'mediacheck-');

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

            $size = filesize($tempFile);
            @unlink($tempFile);

            return $size !== false ? $size : null;
        } catch (Throwable) {
            return null;
        }
    }
}
