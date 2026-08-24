<?php

namespace Tests\Feature\Actions;

use App\Actions\Media\DeleteMedia;
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

class DeleteMediaTest extends TestCase
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

    public function test_local_delete_removes_original_derivatives_and_record(): void
    {
        $media = $this->createMediaWithFiles('public');

        $result = app(DeleteMedia::class)->execute($media);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->localDisk->assertMissing($media->file_path);
        $this->assertDerivativesDeleted($media);
    }

    public function test_local_original_deletion_failure_keeps_record(): void
    {
        Log::spy();
        $media = $this->createMediaWithFiles('public');

        $failingDisk = Mockery::mock(Filesystem::class);
        $failingDisk->shouldReceive('exists')->andReturnTrue();
        $failingDisk->shouldReceive('delete')->andThrow(new RuntimeException('disk unavailable'));

        $this->mockDisks(['public' => $failingDisk]);

        $result = app(DeleteMedia::class)->execute($media);

        $this->assertFalse($result);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->localDisk->assertExists($media->file_path);
        $this->assertDerivativesIntact($media);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_yandex_original_deleted_when_user_confirms(): void
    {
        $media = $this->createMediaWithFiles('yandex_disk');

        $result = app(DeleteMedia::class)->execute($media, deleteRemoteOriginal: true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->remoteDisk->assertMissing($media->file_path);
        $this->assertDerivativesDeleted($media);
    }

    public function test_yandex_original_kept_as_orphan_when_user_declines(): void
    {
        $media = $this->createMediaWithFiles('yandex_disk');

        $result = app(DeleteMedia::class)->execute($media, deleteRemoteOriginal: false);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->remoteDisk->assertExists($media->file_path);
        $this->assertDerivativesDeleted($media);
    }

    public function test_yandex_deletion_failure_keeps_record_when_requested(): void
    {
        Log::spy();
        $media = $this->createMediaWithFiles('yandex_disk');

        $failingDisk = Mockery::mock(Filesystem::class);
        $failingDisk->shouldReceive('exists')->andThrow(new RuntimeException('yandex api unavailable'));

        $this->mockDisks(['yandex_disk' => $failingDisk]);

        $result = app(DeleteMedia::class)->execute($media, deleteRemoteOriginal: true);

        $this->assertFalse($result);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->remoteDisk->assertExists($media->file_path);
        $this->assertDerivativesIntact($media);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_missing_remote_file_does_not_block_deletion(): void
    {
        $path = UploadedFile::fake()->image('photo.jpg', 300, 300)->storeAs('images', 'photo.jpg', 'yandex_disk');

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'yandex_disk',
        ]);
        $media->refresh();

        $this->remoteDisk->delete($path);

        $result = app(DeleteMedia::class)->execute($media, deleteRemoteOriginal: true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_derivative_failure_does_not_block_record_deletion(): void
    {
        Log::spy();
        $media = $this->createMediaWithFiles('yandex_disk');

        $failingThumbnails = Mockery::mock(Filesystem::class);
        $failingThumbnails->shouldReceive('delete')->andThrow(new RuntimeException('thumbnails disk locked'));

        $this->mockDisks(['thumbnails' => $failingThumbnails]);

        $result = app(DeleteMedia::class)->execute($media, deleteRemoteOriginal: true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->remoteDisk->assertMissing($media->file_path);
        Log::shouldHaveReceived('warning')->atLeast()->once();
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

    protected function createMediaWithFiles(string $disk): Media
    {
        UploadedFile::fake()->image('photo.jpg', 600, 400)->storeAs('images', 'photo.jpg', $disk);

        $media = Media::query()->create([
            'file_path' => 'images/photo.jpg',
            'disk' => $disk,
            'collection' => 'gallery',
        ]);

        $media->refresh();

        $this->assertNotNull($media->thumbnail_path, 'Test precondition: thumbnail must exist');
        $this->assertDerivativesIntact($media);

        return $media;
    }

    protected function assertDerivativesIntact(Media $media): void
    {
        if ($media->thumbnail_path) {
            $this->thumbnailDisk->assertExists($media->thumbnail_path);
        }

        foreach ($this->cachePaths($media) as $path) {
            $this->cacheDisk->assertExists($path);
        }
    }

    protected function assertDerivativesDeleted(Media $media): void
    {
        if ($media->thumbnail_path) {
            $this->thumbnailDisk->assertMissing($media->thumbnail_path);
        }

        foreach ($this->cachePaths($media) as $path) {
            $this->cacheDisk->assertMissing($path);
        }
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
