<?php

namespace Tests\Feature\Actions;

use App\Actions\Media\MigrateMediaToYandexDisk;
use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MigrateMediaToYandexDiskTest extends TestCase
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

    public function test_migrates_local_original_and_deletes_it_after_verification(): void
    {
        $media = $this->createLocalMedia();
        $originalContent = $this->localDisk->get((string) $media->file_path);
        $staleCachePaths = $this->cachePaths($media);

        foreach ($staleCachePaths as $path) {
            $this->cacheDisk->assertExists($path);
        }

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isMigrated());
        $this->assertTrue($result->localDeleted);

        $media->refresh();
        $this->assertSame(MigrateMediaToYandexDisk::TARGET_DISK, $media->disk);
        $this->assertSame('images/photo.jpg', $media->file_path);

        $this->localDisk->assertMissing('images/photo.jpg');
        $this->remoteDisk->assertExists('images/photo.jpg');
        $this->assertSame($originalContent, $this->remoteDisk->get('images/photo.jpg'));

        $this->thumbnailDisk->assertExists((string) $media->thumbnail_path);

        foreach ($staleCachePaths as $path) {
            $this->cacheDisk->assertMissing($path);
        }
    }

    public function test_media_already_on_target_disk_is_skipped(): void
    {
        $media = $this->createRemoteMedia();

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('уже на Яндекс.Диске', (string) $result->reason);
        $this->remoteDisk->assertExists('images/photo.jpg');
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'yandex_disk']);
    }

    public function test_missing_local_original_is_reported_and_untouched(): void
    {
        Log::spy();
        $media = $this->createMediaRow('public', 'ghosts/missing.jpg');

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('локальный оригинал отсутствует', (string) $result->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->assertCount(0, $this->remoteDisk->allFiles());
        $this->localDisk->assertMissing('ghosts/missing.jpg');
    }

    public function test_upload_failure_keeps_local_original_and_record(): void
    {
        Log::spy();
        $media = $this->createLocalMedia();

        $failingRemote = Mockery::mock(Filesystem::class);
        $failingRemote->shouldReceive('exists')->once()->andReturnFalse();
        $failingRemote->shouldReceive('directoryExists')->andReturnTrue();
        $failingRemote->shouldReceive('put')
            ->once()
            ->andThrow(new RuntimeException('yandex api unavailable'));

        $this->mockDisks(['yandex_disk' => $failingRemote]);

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isFailed());
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $media->file_path);
        $this->thumbnailDisk->assertExists((string) $media->thumbnail_path);
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_verification_failure_removes_broken_upload_and_keeps_local(): void
    {
        Log::spy();
        $media = $this->createLocalMedia();

        $lyingRemote = Mockery::mock(Filesystem::class);
        $lyingRemote->shouldReceive('exists')->once()->andReturnFalse();
        $lyingRemote->shouldReceive('directoryExists')->andReturnTrue();
        $lyingRemote->shouldReceive('put')->once()->andReturnTrue();
        $lyingRemote->shouldReceive('size')->once()->andReturn(999999);
        $lyingRemote->shouldReceive('delete')->once()->with('images/photo.jpg')->andReturnTrue();

        $this->mockDisks(['yandex_disk' => $lyingRemote]);

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('проверка', (string) $result->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $media->file_path);
    }

    public function test_db_update_failure_keeps_local_original(): void
    {
        Log::spy();
        $media = $this->createLocalMedia();
        $originalContent = $this->localDisk->get((string) $media->file_path);

        $unpersistable = UnpersistableMedia::query()->findOrFail($media->id);

        $result = app(MigrateMediaToYandexDisk::class)->execute($unpersistable);

        $this->assertTrue($result->isFailed());

        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $media->file_path);
        $this->assertSame($originalContent, $this->localDisk->get((string) $media->file_path));

        $this->remoteDisk->assertExists('images/photo.jpg');
    }

    public function test_identical_remote_file_is_reused_without_reupload(): void
    {
        $media = $this->createLocalMedia();
        $this->remoteDisk->put('images/photo.jpg', $this->localDisk->get((string) $media->file_path));

        $remoteSpy = Mockery::mock($this->remoteDisk)->makePartial();
        $remoteSpy->shouldNotReceive('put');

        $this->mockDisks(['yandex_disk' => $remoteSpy]);

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isMigrated());
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'yandex_disk']);
        $this->localDisk->assertMissing((string) $media->file_path);
        $this->remoteDisk->assertExists('images/photo.jpg');
    }

    public function test_conflicting_remote_file_fails_and_stays_untouched(): void
    {
        Log::spy();
        $media = $this->createLocalMedia();
        $foreignContent = 'totally different bytes';

        $this->remoteDisk->put('images/photo.jpg', $foreignContent);

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('другим содержимым', (string) $result->reason);
        $this->assertSame($foreignContent, $this->remoteDisk->get('images/photo.jpg'));
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $media->file_path);
    }

    public function test_rerun_after_success_is_skipped(): void
    {
        $media = $this->createLocalMedia();
        $migrator = app(MigrateMediaToYandexDisk::class);

        $first = $migrator->execute($media);
        $this->assertTrue($first->isMigrated());

        $second = $migrator->execute($media->refresh());

        $this->assertTrue($second->isSkipped());
        $this->assertStringContainsString('уже на Яндекс.Диске', (string) $second->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'yandex_disk']);
    }

    public function test_non_image_media_is_skipped(): void
    {
        $media = $this->createMediaRow('public', 'files/doc.pdf', mime: 'application/pdf');

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('не изображение', (string) $result->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
    }

    public function test_unknown_mime_type_is_skipped(): void
    {
        $media = $this->createMediaRow('public', 'images/unknown.jpg', mime: null);

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('не изображение', (string) $result->reason);
    }

    public function test_derivative_storage_records_are_never_migrated(): void
    {
        UploadedFile::fake()->image('thumb.webp')->storeAs('thumbs', 'thumb.webp', 'thumbnails');

        $media = new Media([
            'file_path' => 'thumbs/thumb.webp',
            'disk' => 'thumbnails',
            'mime_type' => 'image/webp',
        ]);
        $media->saveQuietly();

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('производные', (string) $result->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'thumbnails']);
        Storage::disk('thumbnails')->assertExists('thumbs/thumb.webp');
    }

    public function test_unknown_disk_is_skipped(): void
    {
        $media = $this->createMediaRow('legacy_ftp', 'images/photo.jpg');

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('неизвестный диск', (string) $result->reason);
    }

    public function test_unsupported_remote_driver_disk_is_skipped(): void
    {
        config(['filesystems.disks.s3_archive' => ['driver' => 's3']]);
        $media = $this->createMediaRow('s3_archive', 'images/photo.jpg');

        $result = app(MigrateMediaToYandexDisk::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('неподдерживаемый диск-источник', (string) $result->reason);
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
        UploadedFile::fake()->image('photo.jpg', 600, 400)->storeAs(
            dirname($path),
            basename($path),
            'public',
        );

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        $media->refresh();

        $this->assertNotNull($media->thumbnail_path, 'Test precondition: thumbnail must exist');

        return $media;
    }

    protected function createRemoteMedia(): Media
    {
        UploadedFile::fake()->image('photo.jpg', 600, 400)->storeAs('images', 'photo.jpg', 'yandex_disk');

        $media = Media::query()->create([
            'file_path' => 'images/photo.jpg',
            'disk' => 'yandex_disk',
            'collection' => 'gallery',
        ]);

        $media->refresh();

        return $media;
    }

    protected function createMediaRow(string $disk, string $path, ?string $mime = 'image/jpeg'): Media
    {
        $media = new Media([
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $mime,
        ]);
        $media->saveQuietly();

        return $media->refresh();
    }

    /**
     * @return array<string>
     */
    protected function cachePaths(Media $media): array
    {
        $service = app(ImageCacheService::class);

        return array_map(
            fn (string $tier): string => $service->relativePath($media, $tier),
            array_keys($service->tiers()),
        );
    }
}

/**
 * Модель с «падающим» save() для проверки сбоя обновления БД.
 */
class UnpersistableMedia extends Media
{
    protected $table = 'media';

    public function save(array $options = []): bool
    {
        throw new RuntimeException('db update failed');
    }
}
