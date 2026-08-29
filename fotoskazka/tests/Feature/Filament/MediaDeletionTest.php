<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MediaDeletionTest extends TestCase
{
    use AdminTestCase;

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

    public function test_bulk_delete_declining_remote_originals_keeps_yandex_files(): void
    {
        [$local, $remote] = $this->createMixedMedia();

        Livewire::test(ListMedia::class)
            ->callTableBulkAction(
                'delete',
                [$local->getKey(), $remote->getKey()],
                ['delete_remote_original' => '0'],
            );

        $this->assertDatabaseMissing('media', ['id' => $local->id]);
        $this->assertDatabaseMissing('media', ['id' => $remote->id]);

        $this->localDisk->assertMissing($local->file_path);
        $this->assertDerivativesDeleted($local);

        $this->remoteDisk->assertExists($remote->file_path);
        $this->assertDerivativesDeleted($remote);
    }

    public function test_bulk_delete_confirming_removes_yandex_originals(): void
    {
        [$local, $remote] = $this->createMixedMedia();

        Livewire::test(ListMedia::class)
            ->callTableBulkAction(
                'delete',
                [$local->getKey(), $remote->getKey()],
                ['delete_remote_original' => '1'],
            )
            ->assertNotified('Выбранные медиа удалены');

        $this->assertDatabaseMissing('media', ['id' => $local->id]);
        $this->assertDatabaseMissing('media', ['id' => $remote->id]);

        $this->localDisk->assertMissing($local->file_path);
        $this->remoteDisk->assertMissing($remote->file_path);
        $this->assertDerivativesDeleted($remote);
    }

    public function test_bulk_delete_without_yandex_media_uses_plain_confirmation(): void
    {
        [$local, $secondLocal] = [$this->createMediaWithFiles('public'), $this->createMediaWithFiles('public', 'photo-2.jpg')];

        Livewire::test(ListMedia::class)
            ->callTableBulkAction(
                'delete',
                [$local->getKey(), $secondLocal->getKey()],
            )
            ->assertNotified();

        $this->assertDatabaseMissing('media', ['id' => $local->id]);
        $this->assertDatabaseMissing('media', ['id' => $secondLocal->id]);
        $this->localDisk->assertMissing($local->file_path);
        $this->localDisk->assertMissing($secondLocal->file_path);
    }

    public function test_bulk_partial_failure_keeps_failed_record_and_reports_counts(): void
    {
        Log::spy();

        [$local, $failingRemote, $okRemote] = $this->createPartialFailureMedia();

        $realYandex = $this->remoteDisk;

        $mock = Mockery::mock(Filesystem::class);
        $mock->shouldReceive('exists')->andReturnUsing(fn (string $path): bool => $realYandex->exists($path));
        $mock->shouldReceive('delete')->andReturnUsing(function (string $path) use ($realYandex): bool {
            if (str_contains($path, 'fail-me')) {
                throw new RuntimeException('yandex api error');
            }

            return (bool) $realYandex->delete($path);
        });

        foreach (['public' => $this->localDisk, 'thumbnails' => $this->thumbnailDisk, 'image_cache' => $this->cacheDisk] as $name => $disk) {
            Storage::shouldReceive('disk')->with($name)->andReturn($disk);
        }
        Storage::shouldReceive('disk')->with('yandex_disk')->andReturn($mock);

        Livewire::test(ListMedia::class)
            ->callTableBulkAction(
                'delete',
                collect([$local->getKey(), $failingRemote->getKey(), $okRemote->getKey()]),
                ['delete_remote_original' => '1'],
            )
            ->assertNotified('Удалено файлов: 2 из 3');

        $this->assertDatabaseHas('media', ['id' => $failingRemote->id]);
        $this->assertDatabaseMissing('media', ['id' => $local->id]);
        $this->assertDatabaseMissing('media', ['id' => $okRemote->id]);

        $this->remoteDisk->assertExists($failingRemote->file_path);

        Log::shouldHaveReceived('error')->once();
    }

    public function test_single_delete_local_media_removes_files_and_record(): void
    {
        $media = $this->createMediaWithFiles('public');

        Livewire::test(EditMedia::class, ['record' => $media->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->localDisk->assertMissing($media->file_path);
        $this->assertDerivativesDeleted($media);
    }

    public function test_single_delete_confirming_removes_yandex_original(): void
    {
        $media = $this->createMediaWithFiles('yandex_disk');

        Livewire::test(EditMedia::class, ['record' => $media->getKey()])
            ->callAction('delete', arguments: ['delete_remote_original' => true]);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->remoteDisk->assertMissing($media->file_path);
        $this->assertDerivativesDeleted($media);
    }

    public function test_single_delete_default_choice_keeps_yandex_file(): void
    {
        $media = $this->createMediaWithFiles('yandex_disk');

        Livewire::test(EditMedia::class, ['record' => $media->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDerivativesDeleted($media);
        $this->remoteDisk->assertExists($media->file_path);
    }

    /**
     * @return array{0: Media, 1: Media}
     */
    protected function createMixedMedia(): array
    {
        return [
            $this->createMediaWithFiles('public'),
            $this->createMediaWithFiles('yandex_disk'),
        ];
    }

    /**
     * @return array{0: Media, 1: Media, 2: Media}
     */
    protected function createPartialFailureMedia(): array
    {
        return [
            $this->createMediaWithFiles('public'),
            $this->createMediaWithFiles('yandex_disk', 'fail-me.jpg'),
            $this->createMediaWithFiles('yandex_disk', 'ok.jpg'),
        ];
    }

    protected function createMediaWithFiles(string $disk, string $filename = 'photo.jpg'): Media
    {
        UploadedFile::fake()->image($filename, 600, 400)->storeAs('images', $filename, $disk);

        $media = Media::query()->create([
            'file_path' => 'images/'.$filename,
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
