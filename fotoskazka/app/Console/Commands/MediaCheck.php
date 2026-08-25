<?php

namespace App\Console\Commands;

use App\Actions\Media\CheckMediaIntegrity;
use App\Actions\Media\MediaCheckResult;
use App\Models\Media;
use App\Services\MediaProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaCheck extends Command
{
    protected $signature = 'media:check
                            {--fix-thumbnails : Regenerate missing thumbnails using MediaProcessor}
                            {--media-id= : Check only a specific Media record}
                            {--limit= : Limit the number of Media records to check}';

    protected $description = 'Проверка целостности Media Storage и обнаружение orphan-файлов';

    public function handle(CheckMediaIntegrity $checker, MediaProcessor $processor): int
    {
        $fixThumbnails = (bool) $this->option('fix-thumbnails');
        $limit = max(0, (int) $this->option('limit'));

        $query = Media::query()->orderBy('id');

        if ($specificId = $this->option('media-id')) {
            if (! Media::whereKey($specificId)->exists()) {
                $this->error("Media [{$specificId}] не найдена.");

                return self::FAILURE;
            }

            $query->whereKey($specificId);
        }

        $mediaList = $query->get();
        $stats = $this->checkMedia($mediaList, $checker, $limit);

        $orphanCount = $this->scanYandexOrphans($mediaList);

        $this->report($stats, $orphanCount);

        if ($fixThumbnails) {
            $fixed = $this->fixThumbnails($stats['missing_thumbnail_ids'], $processor);
            $this->newLine();
            $this->info("Восстановлено thumbnails: {$fixed}");
        }

        return $stats['error'] + $stats['missing_original'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{checked: int, skipped: int, ok: int, missing_original: int, missing_thumbnail: int, missing_image_cache: int, metadata_mismatch: int, error: int, missing_thumbnail_ids: int[], results: array<int, array{id: int, file_path: string, disk: string, status: string, detail: string|null}>}
     */
    protected function checkMedia($mediaList, CheckMediaIntegrity $checker, int $limit): array
    {
        $stats = [
            'checked' => 0,
            'skipped' => 0,
            'ok' => 0,
            'missing_original' => 0,
            'missing_thumbnail' => 0,
            'missing_image_cache' => 0,
            'metadata_mismatch' => 0,
            'error' => 0,
            'missing_thumbnail_ids' => [],
            'results' => [],
        ];

        foreach ($mediaList as $media) {
            $stats['checked']++;

            if ($limit > 0 && $stats['checked'] > $limit) {
                $stats['skipped']++;

                continue;
            }

            $result = $checker->check($media);

            $statusKey = match ($result->status) {
                MediaCheckResult::VALID => 'ok',
                MediaCheckResult::MISSING_ORIGINAL => 'missing_original',
                MediaCheckResult::MISSING_THUMBNAIL => 'missing_thumbnail',
                MediaCheckResult::MISSING_IMAGE_CACHE => 'missing_image_cache',
                MediaCheckResult::METADATA_MISMATCH => 'metadata_mismatch',
                MediaCheckResult::ERROR => 'error',
                default => 'error',
            };

            $stats[$statusKey]++;

            if ($result->isMissingThumbnail()) {
                $stats['missing_thumbnail_ids'][] = $media->id;
            }

            $stats['results'][] = [
                'id' => (int) $media->id,
                'file_path' => (string) $media->file_path,
                'disk' => (string) $media->disk,
                'status' => $result->status,
                'detail' => $result->detail,
            ];
        }

        return $stats;
    }

    protected function report(array $stats, int $orphanCount): void
    {
        $this->newLine();
        $this->info('Checked: '.$stats['checked']);

        if ($stats['skipped'] > 0) {
            $this->line('Skipped (limit): '.$stats['skipped']);
        }

        $this->info('OK: '.$stats['ok']);

        if ($stats['missing_original'] > 0) {
            $this->warn('Missing original: '.$stats['missing_original']);
        }

        if ($stats['missing_thumbnail'] > 0) {
            $this->warn('Missing thumbnail: '.$stats['missing_thumbnail']);
        }

        if ($stats['missing_image_cache'] > 0) {
            $this->warn('Missing image cache: '.$stats['missing_image_cache']);
        }

        if ($stats['metadata_mismatch'] > 0) {
            $this->warn('Metadata mismatch: '.$stats['metadata_mismatch']);
        }

        if ($stats['error'] > 0) {
            $this->error('Errors: '.$stats['error']);
        }

        if ($orphanCount > 0) {
            $this->warn('Potential orphan Yandex files: '.$orphanCount);
        }

        $this->newLine();

        if ($stats['checked'] === 0) {
            $this->info('Медиа для проверки не найдено.');

            return;
        }

        $rows = [];

        foreach ($stats['results'] as $item) {
            $statusLabel = match ($item['status']) {
                MediaCheckResult::VALID => '<info>OK</info>',
                MediaCheckResult::MISSING_ORIGINAL => '<error>missing original</error>',
                MediaCheckResult::MISSING_THUMBNAIL => '<comment>missing thumbnail</comment>',
                MediaCheckResult::MISSING_IMAGE_CACHE => '<comment>missing image cache</comment>',
                MediaCheckResult::METADATA_MISMATCH => '<comment>metadata mismatch</comment>',
                MediaCheckResult::ERROR => '<error>error</error>',
                default => $item['status'],
            };

            $detail = $item['detail'] ?? '';

            $rows[] = [$item['id'], $item['file_path'], $item['disk'], $statusLabel, $detail];
        }

        $this->table(['ID', 'Path', 'Disk', 'Status', 'Detail'], $rows);
    }

    /**
     * Scan Yandex Disk for files that have no corresponding Media record.
     *
     * These are POTENTIAL orphans — not automatically errors.
     * A user may have intentionally kept the file when deleting Media via B6 policy.
     */
    protected function scanYandexOrphans($mediaList): int
    {
        if (config('filesystems.disks.yandex_disk') === null) {
            $this->line('Yandex Disk not configured — skipping orphan scan.');

            return 0;
        }

        $token = config('filesystems.disks.yandex_disk.token');

        if (blank($token)) {
            $this->line('Yandex Disk token not configured — skipping orphan scan.');

            return 0;
        }

        try {
            $disk = Storage::disk('yandex_disk');
            $root = (string) config('filesystems.disks.yandex_disk.root', '');

            $allFiles = $disk->allFiles();

            $dbPaths = $mediaList
                ->where('disk', 'yandex_disk')
                ->pluck('file_path')
                ->filter()
                ->map(fn ($path) => (string) $path)
                ->values()
                ->all();

            $dbPathSet = array_flip($dbPaths);

            $orphans = [];

            foreach ($allFiles as $file) {
                if (! isset($dbPathSet[$file])) {
                    $orphans[] = $file;
                }
            }

            if ($orphans !== []) {
                $this->newLine();
                $this->warn('Potential orphan files on Yandex Disk:'.PHP_EOL.implode(PHP_EOL, $orphans));
            }

            return count($orphans);
        } catch (Throwable $exception) {
            $this->warn('Unable to scan Yandex Disk for orphans: '.$exception->getMessage());

            return 0;
        }
    }

    protected function fixThumbnails(array $mediaIds, MediaProcessor $processor): int
    {
        if ($mediaIds === []) {
            $this->info('No missing thumbnails to fix.');

            return 0;
        }

        $mediaList = Media::whereIn('id', $mediaIds)
            ->where('mime_type', 'like', 'image/%')
            ->get();

        $fixed = 0;

        foreach ($mediaList as $media) {
            $processor->process($media, force: true);

            $media->refresh();

            if ($media->thumbnail_path && Storage::disk('thumbnails')->exists($media->thumbnail_path)) {
                $fixed++;
            }
        }

        return $fixed;
    }
}
