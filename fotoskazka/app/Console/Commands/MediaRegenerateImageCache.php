<?php

namespace App\Console\Commands;

use App\Jobs\RegenerateMediaImageCache;
use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Console\Command;

class MediaRegenerateImageCache extends Command
{
    protected $signature = 'media:regenerate-image-cache
                            {--force : Force regeneration even if cache variants exist}
                            {--dry-run : Show what would be regenerated without dispatching jobs}
                            {--limit= : Limit number of media to dispatch}
                            {--id= : Process specific media ID}';

    protected $description = 'Регенерирует кэш производных изображений (display/lightbox) в фоновом режиме через очередь';

    public function handle(ImageCacheService $cache): int
    {
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit');
        $specificId = $this->option('id');

        $query = Media::whereNotNull('file_path')
            ->where('mime_type', 'like', 'image/%');

        if ($specificId) {
            $query->where('id', $specificId);
        }

        $media = $query->orderBy('id')->get()->filter(
            fn (Media $m) => $force || $this->needsRegeneration($m, $cache)
        )->values();

        if ($limit) {
            $media = $media->take((int) $limit);
        }

        if ($media->isEmpty()) {
            $this->info('No media found matching criteria.');

            return self::SUCCESS;
        }

        $this->info("Found {$media->count()} media to regenerate.");

        if ($dryRun) {
            $this->table(['ID', 'File Path', 'Disk', 'Reason'], $media->map(function ($m) use ($force, $cache) {
                return [
                    $m->id,
                    $m->file_path,
                    $m->disk,
                    $force ? 'forced' : $this->missingVariantsReason($m, $cache),
                ];
            })->toArray());

            $this->warn('Dry run — no jobs dispatched.');

            return self::SUCCESS;
        }

        foreach ($media as $m) {
            RegenerateMediaImageCache::dispatch($m->id, $force);
        }

        $this->info("Dispatched {$media->count()} job(s). Worker queue:work выполнит регенерацию в фоне.");

        return self::SUCCESS;
    }

    protected function needsRegeneration(Media $media, ImageCacheService $cache): bool
    {
        return $this->missingVariantsReason($media, $cache) !== null;
    }

    protected function missingVariantsReason(Media $media, ImageCacheService $cache): ?string
    {
        $missing = [];

        foreach (array_keys($cache->tiers()) as $tier) {
            if (! $cache->isCached($media, (string) $tier)) {
                $missing[] = $tier;
            }
        }

        return $missing === [] ? null : 'missing: '.implode(', ', $missing);
    }
}
