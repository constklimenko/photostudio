<?php

namespace App\Console\Commands;

use App\Services\ImageCacheService;
use Illuminate\Console\Command;

class MediaPruneImageCache extends Command
{
    protected $signature = 'media:prune-image-cache
                            {--all : Удалить весь кэш независимо от лимита}
                            {--stats : Только показать статистику}';

    protected $description = 'Очистка кэша производных изображений (display/lightbox) при превышении лимита размера';

    public function handle(ImageCacheService $cache): int
    {
        $totalMb = round($cache->totalSize() / 1024 / 1024, 2);
        $limitMb = (int) config('filesystems.image_cache.max_size_mb', 2048);

        $this->info("Размер кэша: {$totalMb} МБ (лимит: {$limitMb} МБ)");

        if ($this->option('stats')) {
            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $cache->clear();
            $this->info('Кэш полностью очищен.');

            return self::SUCCESS;
        }

        $freed = $cache->purgeToLimit();

        if ($freed === 0) {
            $this->info('Лимит не превышен — очистка не требуется.');

            return self::SUCCESS;
        }

        $freedMb = round($freed / 1024 / 1024, 2);
        $this->info("Освобождено: {$freedMb} МБ. Текущий размер: "
            .round($cache->totalSize() / 1024 / 1024, 2).' МБ.');

        return self::SUCCESS;
    }
}
