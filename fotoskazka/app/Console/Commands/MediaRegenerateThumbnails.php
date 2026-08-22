<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaProcessor;
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

    public function handle(MediaProcessor $processor): int
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
            if ($processor->process($m, force: true)) {
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
}
