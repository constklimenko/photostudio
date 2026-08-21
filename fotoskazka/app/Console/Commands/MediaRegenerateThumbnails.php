<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MediaRegenerateThumbnails extends Command
{
    protected $signature = 'media:regenerate-thumbnails
                            {--dry-run : Show what would be done without making changes}
                            {--force : Force regeneration even if thumbnail exists}
                            {--limit= : Limit number of media to process}
                            {--id= : Process specific media ID}';

    protected $description = 'Regenerate WebP thumbnails for media records';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $limit = $this->option('limit');
        $specificId = $this->option('id');

        $query = Media::whereNotNull('file_path')
            ->where(function ($q) {
                $q->where('mime_type', 'like', 'image/%')
                    ->orWhereNull('mime_type');
            });

        if ($specificId) {
            $query->where('id', $specificId);
        }

        $media = $query->orderBy('id')->get()->filter(
            fn (Media $m) => $force || $this->needsRegeneration($m)
        )->values();

        if ($limit) {
            $media = $media->take((int) $limit);
        }

        if ($media->isEmpty()) {
            $this->info('No media found matching criteria.');

            return self::SUCCESS;
        }

        $this->info("Found {$media->count()} media to process.");

        if ($dryRun) {
            $this->table(['ID', 'File Path', 'Current Thumbnail', 'Disk', 'Reason'], $media->map(function ($m) use ($force) {
                return [
                    $m->id,
                    $m->file_path,
                    $m->thumbnail_path ?? '—',
                    $m->disk,
                    $force ? 'forced' : $this->regenerationReason($m),
                ];
            })->toArray());

            $this->warn('Dry run — no changes made.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($media->count());
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $success = 0;
        $failed = 0;

        foreach ($media as $m) {
            $result = $this->regenerateThumbnail($m);

            if ($result) {
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done. Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }

    protected function needsRegeneration(Media $media): bool
    {
        return $this->regenerationReason($media) !== null;
    }

    protected function regenerationReason(Media $media): ?string
    {
        if (blank($media->thumbnail_path)) {
            return 'no thumbnail';
        }

        if (str_contains($media->thumbnail_path, 'thumbnails/thumbnails/')
            || str_contains($media->thumbnail_path, '/./')) {
            return 'broken path';
        }

        if (! Storage::disk('thumbnails')->exists($media->thumbnail_path)) {
            return 'file missing';
        }

        return null;
    }

    protected function regenerateThumbnail(Media $media): bool
    {
        $disk = $media->disk ?? 'public';
        $path = $media->file_path;

        if (! Storage::disk($disk)->exists($path)) {
            $this->warn("File not found: {$disk}/{$path}");

            return false;
        }

        $stream = Storage::disk($disk)->readStream($path);
        if (! $stream) {
            $this->warn("Cannot read stream: {$disk}/{$path}");

            return false;
        }

        $meta = stream_get_meta_data($stream);
        $tempPath = $meta['uri'] ?? null;
        fclose($stream);

        if (! $tempPath) {
            $this->warn("No temp path for stream: {$disk}/{$path}");

            return false;
        }

        $mimeType = mime_content_type($tempPath) ?: null;
        if (! $mimeType || ! str_starts_with($mimeType, 'image/')) {
            $this->warn("Not an image: {$disk}/{$path} ({$mimeType})");

            return false;
        }

        $imageInfo = getimagesize($tempPath);
        if (! $imageInfo) {
            $this->warn("Cannot get image size: {$disk}/{$path}");

            return false;
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

        $srcImage = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($tempPath),
            'image/png' => imagecreatefrompng($tempPath),
            'image/webp' => imagecreatefromwebp($tempPath),
            'image/gif' => imagecreatefromgif($tempPath),
            default => null,
        };

        if (! $srcImage) {
            $this->warn("Cannot create image resource: {$disk}/{$path}");

            return false;
        }

        $thumbImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $info = pathinfo($path);
        $thumbDir = ($info['dirname'] ?? '') !== '.' ? ($info['dirname'] ?? '') : '';
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

        $media->update(['thumbnail_path' => $thumbPath]);

        return true;
    }
}
