<?php

namespace Tests\Feature\Console;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MediaMigrateToYandexCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Filesystem $localDisk;

    protected Filesystem $remoteDisk;

    protected Filesystem $thumbnailDisk;

    protected Filesystem $cacheDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('yandex_disk');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');

        $this->localDisk = Storage::disk('public');
        $this->remoteDisk = Storage::disk('yandex_disk');
        $this->thumbnailDisk = Storage::disk('thumbnails');
        $this->cacheDisk = Storage::disk(config('filesystems.image_cache.disk'));
    }

    public function test_dry_run_reports_plan_without_changes(): void
    {
        $first = $this->createLocalMedia('images/one.jpg');
        $second = $this->createLocalMedia('images/two.jpg');
        $remote = $this->createRemoteMedia();
        $ghost = $this->createGhostMedia('images/ghost.jpg');

        $this->artisan('media:migrate-to-yandex', ['--dry-run' => true])
            ->expectsOutputToContain('Найдено записей: 4')
            ->expectsOutputToContain('Доступно к миграции: 2')
            ->expectsOutputToContain('Пропущено: 2')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        foreach ([$first, $second] as $media) {
            $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
            $this->localDisk->assertExists($media->file_path);
        }

        $this->assertDatabaseHas('media', ['id' => $remote->id, 'disk' => 'yandex_disk']);
        $this->assertDatabaseHas('media', ['id' => $ghost->id, 'disk' => 'public']);

        $this->assertCount(1, $this->remoteDisk->allFiles(), 'Dry run must not upload anything.');
    }

    public function test_migrates_eligible_media_end_to_end(): void
    {
        $media = $this->createLocalMedia();
        $content = $this->localDisk->get((string) $media->file_path);

        $this->artisan('media:migrate-to-yandex')
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 0, с ошибками 0, локально удалено 1')
            ->assertSuccessful();

        $media->refresh();
        $this->assertSame('yandex_disk', $media->disk);
        $this->localDisk->assertMissing((string) $media->file_path);
        $this->assertSame($content, $this->remoteDisk->get((string) $media->file_path));
        $this->thumbnailDisk->assertExists((string) $media->thumbnail_path);
    }

    public function test_repeated_execution_is_idempotent(): void
    {
        $media = $this->createLocalMedia();

        $this->artisan('media:migrate-to-yandex')->assertSuccessful();

        $this->artisan('media:migrate-to-yandex')
            ->expectsOutputToContain('Пропущена Media #'.$media->id.': уже на Яндекс.Диске')
            ->expectsOutputToContain('Итог: обработано 0, мигрировано 0, пропущено 1, с ошибками 0, локально удалено 0')
            ->assertSuccessful();

        $media->refresh();
        $this->assertSame('yandex_disk', $media->disk);
        $this->assertCount(1, $this->remoteDisk->allFiles());
        $this->localDisk->assertMissing((string) $media->file_path);
    }

    public function test_limit_processes_only_first_candidates(): void
    {
        $first = $this->createLocalMedia('images/one.jpg');
        $second = $this->createLocalMedia('images/two.jpg');
        $third = $this->createLocalMedia('images/three.jpg');

        $this->artisan('media:migrate-to-yandex', ['--limit' => 1])
            ->expectsOutputToContain('сверх лимита')
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 2, с ошибками 0, локально удалено 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $first->id, 'disk' => 'yandex_disk']);
        $this->assertDatabaseHas('media', ['id' => $second->id, 'disk' => 'public']);
        $this->assertDatabaseHas('media', ['id' => $third->id, 'disk' => 'public']);
    }

    public function test_media_id_option_migrates_only_that_record(): void
    {
        $untouched = $this->createLocalMedia('images/one.jpg');
        $target = $this->createLocalMedia('images/two.jpg');

        $this->artisan('media:migrate-to-yandex', ['--media-id' => $target->id])
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 0, с ошибками 0, локально удалено 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $untouched->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $untouched->file_path);

        $target->refresh();
        $this->assertSame('yandex_disk', $target->disk);
        $this->localDisk->assertMissing((string) $target->file_path);
    }

    public function test_nonexistent_media_id_fails(): void
    {
        $this->artisan('media:migrate-to-yandex', ['--media-id' => 99999])
            ->expectsOutputToContain('не найдена')
            ->assertExitCode(1);
    }

    public function test_failed_item_does_not_stop_batch_and_keeps_local(): void
    {
        $good = $this->createLocalMedia();
        $broken = $this->createGhostMedia('images/missing.jpg');

        $this->artisan('media:migrate-to-yandex')
            ->expectsOutputToContain('Пропущена Media #'.$broken->id.': локальный оригинал отсутствует')
            ->expectsOutputToContain('Итог: обработано 1, мигрировано 1, пропущено 1, с ошибками 0, локально удалено 1')
            ->assertSuccessful();

        $good->refresh();
        $this->assertSame('yandex_disk', $good->disk);
        $this->localDisk->assertMissing((string) $good->file_path);

        $broken->refresh();
        $this->assertSame('public', $broken->disk);
        $this->assertDatabaseHas('media', ['id' => $broken->id]);
    }

    public function test_upload_failures_do_not_stop_batch_and_keep_locals(): void
    {
        Log::spy();
        $first = $this->createLocalMedia('images/one.jpg');
        $second = $this->createLocalMedia('images/two.jpg');

        $failingRemote = Mockery::mock(Filesystem::class);
        $failingRemote->shouldReceive('exists')->andReturnFalse();
        $failingRemote->shouldReceive('directoryExists')->andReturnTrue();
        $failingRemote->shouldReceive('put')->andThrow(new RuntimeException('yandex down'));

        $this->mockDisks(['yandex_disk' => $failingRemote]);

        $this->artisan('media:migrate-to-yandex')
            ->expectsOutputToContain('Итог: обработано 2, мигрировано 0, пропущено 0, с ошибками 2, локально удалено 0')
            ->assertExitCode(1);

        foreach ([$first, $second] as $media) {
            $media->refresh();
            $this->assertSame('public', $media->disk);
            $this->localDisk->assertExists((string) $media->file_path);
            $this->thumbnailDisk->assertExists((string) $media->thumbnail_path);
        }
    }

    /**
     * Подменяет выбранные диски на фасаде Storage; остальные
     * возвращаются как заранее захваченные реальные инстансы.
     *
     * @param  array<string, Filesystem>  $overrides
     */
    protected function mockDisks(array $overrides): void
    {
        foreach (['public', 'yandex_disk', 'thumbnails', 'image_cache'] as $name) {
            Storage::shouldReceive('disk')
                ->with($name)
                ->andReturn($overrides[$name] ?? match ($name) {
                    'public' => $this->localDisk,
                    'yandex_disk' => $this->remoteDisk,
                    'thumbnails' => $this->thumbnailDisk,
                    default => $this->cacheDisk,
                });
        }
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

    protected function createRemoteMedia(): Media
    {
        UploadedFile::fake()->image('remote.jpg', 600, 400)->storeAs('images', 'remote.jpg', 'yandex_disk');

        $media = Media::query()->create([
            'file_path' => 'images/remote.jpg',
            'disk' => 'yandex_disk',
        ]);

        $media->refresh();

        return $media;
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
