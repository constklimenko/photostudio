<?php

namespace Tests\Feature\Console;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaMigrateToS3CommandTest extends TestCase
{
    use RefreshDatabase;

    protected Filesystem $localDisk;

    protected Filesystem $s3Disk;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('s3');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');

        $this->localDisk = Storage::disk('public');
        $this->s3Disk = Storage::disk('s3');
    }

    public function test_dry_run_reports_plan_without_changes(): void
    {
        $first = $this->createLocalMedia('images/one.jpg');
        $second = $this->createLocalMedia('images/two.jpg');
        $remote = $this->createMediaRow('s3', 'images/remote.jpg');
        $ghost = $this->createGhostMedia('images/ghost.jpg');

        $this->artisan('media:migrate-to-s3', ['--dry-run' => true])
            ->expectsOutputToContain('Найдено записей: 4')
            ->expectsOutputToContain('Доступно к миграции: 2')
            ->expectsOutputToContain('Пропущено: 2')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        foreach ([$first, $second] as $media) {
            $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
            $this->localDisk->assertExists($media->file_path);
        }

        $this->assertDatabaseHas('media', ['id' => $remote->id, 'disk' => 's3']);
        $this->assertDatabaseHas('media', ['id' => $ghost->id, 'disk' => 'public']);
        $this->assertCount(0, $this->s3Disk->allFiles(), 'Dry run must not upload anything.');
    }

    public function test_migrates_eligible_media_end_to_end(): void
    {
        $media = $this->createLocalMedia();
        $content = $this->localDisk->get((string) $media->file_path);

        $this->artisan('media:migrate-to-s3')
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 0, с ошибками 0, источников удалено 1')
            ->assertSuccessful();

        $media->refresh();
        $this->assertSame('s3', $media->disk);
        $this->localDisk->assertMissing((string) $media->file_path);
        $this->assertSame($content, $this->s3Disk->get((string) $media->file_path));
    }

    public function test_keep_source_option_preserves_local_original(): void
    {
        $media = $this->createLocalMedia();

        $this->artisan('media:migrate-to-s3', ['--keep-source' => true])
            ->expectsOutputToContain('источников удалено 0')
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 's3']);
        $this->localDisk->assertExists((string) $media->file_path);
        $this->s3Disk->assertExists((string) $media->file_path);
    }

    public function test_repeated_execution_is_idempotent(): void
    {
        $media = $this->createLocalMedia();

        $this->artisan('media:migrate-to-s3')->assertSuccessful();

        $this->artisan('media:migrate-to-s3')
            ->expectsOutputToContain('Пропущена Media #'.$media->id.': уже на S3')
            ->expectsOutputToContain('Итог: обработано 0, мигрировано 0, пропущено 1, с ошибками 0, источников удалено 0')
            ->assertSuccessful();

        $media->refresh();
        $this->assertSame('s3', $media->disk);
        $this->assertCount(1, $this->s3Disk->allFiles());
        $this->localDisk->assertMissing((string) $media->file_path);
    }

    public function test_limit_processes_only_first_candidates(): void
    {
        $first = $this->createLocalMedia('images/one.jpg');
        $second = $this->createLocalMedia('images/two.jpg');

        $this->artisan('media:migrate-to-s3', ['--limit' => 1])
            ->expectsOutputToContain('сверх лимита')
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 1, с ошибками 0, источников удалено 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $first->id, 'disk' => 's3']);
        $this->assertDatabaseHas('media', ['id' => $second->id, 'disk' => 'public']);
    }

    public function test_media_id_option_migrates_only_that_record(): void
    {
        $untouched = $this->createLocalMedia('images/one.jpg');
        $target = $this->createLocalMedia('images/two.jpg');

        $this->artisan('media:migrate-to-s3', ['--media-id' => $target->id])
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 0, с ошибками 0, источников удалено 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $untouched->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $untouched->file_path);

        $target->refresh();
        $this->assertSame('s3', $target->disk);
        $this->localDisk->assertMissing((string) $target->file_path);
    }

    public function test_nonexistent_media_id_fails(): void
    {
        $this->artisan('media:migrate-to-s3', ['--media-id' => 99999])
            ->expectsOutputToContain('не найдена')
            ->assertExitCode(1);
    }

    public function test_missing_source_does_not_stop_batch(): void
    {
        $good = $this->createLocalMedia();
        $broken = $this->createGhostMedia('images/missing.jpg');

        $this->artisan('media:migrate-to-s3')
            ->expectsOutputToContain('Пропущена Media #'.$broken->id.': оригинал отсутствует')
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 1, с ошибками 0, источников удалено 1')
            ->assertSuccessful();

        $good->refresh();
        $this->assertSame('s3', $good->disk);
        $this->localDisk->assertMissing((string) $good->file_path);
        $this->assertDatabaseHas('media', ['id' => $broken->id, 'disk' => 'public']);
    }

    protected function createLocalMedia(string $path = 'images/photo.jpg'): Media
    {
        UploadedFile::fake()->image(basename($path), 600, 400)->storeAs(
            dirname($path),
            basename($path),
            'public',
        );

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);

        $media->refresh();

        return $media;
    }

    protected function createMediaRow(string $disk, string $path): Media
    {
        return Media::query()->create([
            'file_path' => $path,
            'disk' => $disk,
        ]);
    }

    protected function createGhostMedia(string $path): Media
    {
        return Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);
    }
}
