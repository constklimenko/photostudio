<?php

namespace App\Console\Commands;

use App\Actions\Media\MediaMigrationResult;
use App\Actions\Media\MigrateMediaToYandexDisk;
use App\Models\Media;
use Illuminate\Console\Command;
use Throwable;

class MediaMigrateToYandex extends Command
{
    protected $signature = 'media:migrate-to-yandex
                            {--dry-run : Только показать план миграции, ничего не менять}
                            {--limit= : Ограничить число миграций за запуск}
                            {--media-id= : Мигрировать только конкретный Media}';

    protected $description = 'Миграция локальных оригиналов Media на Яндекс.Диск (upload → verify → DB → delete local)';

    public function handle(MigrateMediaToYandexDisk $migrator): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Media::query()->orderBy('id');

        if ($specificId = $this->option('media-id')) {
            if (! Media::whereKey($specificId)->exists()) {
                $this->error("Media [{$specificId}] не найдена.");

                return self::FAILURE;
            }

            $query->whereKey($specificId);
        }

        [$eligible, $skipped] = $this->classify($query->get(), $migrator, $dryRun);

        $limit = max(0, (int) $this->option('limit'));
        $plan = $this->applyLimit($eligible, $skipped, $limit);

        $this->info('Найдено записей: '.$plan['found']);
        $this->info('Доступно к миграции: '.count($plan['toProcess']));
        $this->info('Пропущено: '.count($plan['skipped']));

        if ($plan['found'] === 0) {
            $this->info('Мигрировать нечего.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            return $this->reportDryRun($plan);
        }

        return $this->executeBatch($plan, $migrator);
    }

    /**
     * @return array{0: array<int, Media>, 1: array<int, array{0: Media, 1: string}>}
     */
    protected function classify($all, MigrateMediaToYandexDisk $migrator, bool $dryRun): array
    {
        $eligible = [];
        $skipped = [];

        foreach ($all as $media) {
            $reason = $migrator->eligibilityIssue($media);

            if ($reason === null && $dryRun) {
                $reason = $migrator->remoteConflictReason($media);
            }

            if ($reason === null) {
                $eligible[] = $media;
            } else {
                $skipped[] = [$media, $reason];
            }
        }

        return [$eligible, $skipped];
    }

    /**
     * Лимит применяется после отбора кандидатов; записи сверх лимита
     * учитываются как пропущенные с причиной «сверх лимита».
     */
    protected function applyLimit(array $eligible, array $skipped, int $limit): array
    {
        if ($limit > 0 && count($eligible) > $limit) {
            foreach (array_slice($eligible, $limit) as $media) {
                $skipped[] = [$media, 'сверх лимита (--limit='.$limit.')'];
            }

            $eligible = array_slice($eligible, 0, $limit);
        }

        return [
            'found' => count($eligible) + count($skipped),
            'toProcess' => $eligible,
            'skipped' => $skipped,
        ];
    }

    protected function reportDryRun(array $plan): int
    {
        $rows = [];

        foreach ($plan['toProcess'] as $media) {
            $rows[] = [$media->id, $media->disk, (string) $media->file_path, 'к миграции', '—'];
        }

        foreach ($plan['skipped'] as [$media, $reason]) {
            $rows[] = [$media->id, $media->disk, (string) $media->file_path, 'пропущено', $reason];
        }

        $this->table(['ID', 'Диск', 'Путь', 'Статус', 'Причина'], $rows);

        foreach ($this->reasonBreakdown($plan['skipped']) as $reason => $count) {
            $this->line("— {$reason}: {$count}");
        }

        $this->newLine();
        $this->warn('Dry run — изменения не выполнялись.');

        return self::SUCCESS;
    }

    protected function executeBatch(array $plan, MigrateMediaToYandexDisk $migrator): int
    {
        $total = count($plan['toProcess']);
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s% %memory:6s%');

        $stats = ['processed' => 0, 'migrated' => 0, 'skipped' => 0, 'failed' => 0, 'localDeleted' => 0];

        foreach ($plan['toProcess'] as $media) {
            try {
                $result = $migrator->execute($media);
            } catch (Throwable $exception) {
                $result = new MediaMigrationResult(MediaMigrationResult::FAILED, $exception->getMessage());
            }

            $stats['processed']++;

            match (true) {
                $result->isMigrated() => $stats['migrated']++,
                $result->isSkipped() => $stats['skipped']++,
                default => $stats['failed']++,
            };

            if ($result->localDeleted) {
                $stats['localDeleted']++;
            }

            if ($result->isFailed()) {
                $this->newLine();
                $this->warn("Ошибка Media #{$media->id}: {$result->reason}");
                $this->newLine();
            } elseif ($result->isSkipped()) {
                $this->line("Пропущена Media #{$media->id}: {$result->reason}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        foreach ($plan['skipped'] as [$media, $reason]) {
            $this->line("Пропущена Media #{$media->id}: {$reason}");
        }

        if ($plan['skipped'] !== []) {
            $this->newLine();
        }

        $this->info(sprintf(
            'Итог: обработано %d, мигрировано %d, пропущено %d, с ошибками %d, локально удалено %d',
            $stats['processed'],
            $stats['migrated'],
            $stats['skipped'] + count($plan['skipped']),
            $stats['failed'],
            $stats['localDeleted'],
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<int, array{0: Media, 1: string}>  $skipped
     * @return array<string, int>
     */
    protected function reasonBreakdown(array $skipped): array
    {
        $breakdown = [];

        foreach ($skipped as [, $reason]) {
            $breakdown[$reason] = ($breakdown[$reason] ?? 0) + 1;
        }

        ksort($breakdown);

        return $breakdown;
    }
}
