<?php

namespace Tests\Feature\Actions;

use App\Actions\Media\MigrateMediaToS3;
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

class MigrateMediaToS3Test extends TestCase
{
    use RefreshDatabase;

    protected Filesystem $localDisk;

    protected Filesystem $s3Disk;

    protected Filesystem $thumbnailDisk;

    protected Filesystem $cacheDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('s3');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');

        $this->localDisk = Storage::disk('public');
        $this->s3Disk = Storage::disk('s3');
        $this->thumbnailDisk = Storage::disk('thumbnails');
        $this->cacheDisk = Storage::disk(config('filesystems.image_cache.disk'));
    }

    public function test_migrates_local_image_and_deletes_source_after_verification(): void
    {
        $media = $this->createLocalMedia();
        $content = $this->localDisk->get((string) $media->file_path);
        $staleCachePaths = $this->cachePaths($media);

        foreach ($staleCachePaths as $path) {
            $this->cacheDisk->assertExists($path);
        }

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isMigrated());
        $this->assertTrue($result->localDeleted);

        $media->refresh();
        $this->assertSame('s3', $media->disk);
        $this->assertSame('images/photo.jpg', $media->file_path);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertNotNull($media->width);
        $this->assertNotNull($media->height);

        $this->localDisk->assertMissing('images/photo.jpg');
        $this->s3Disk->assertExists('images/photo.jpg');
        $this->assertSame($content, $this->s3Disk->get('images/photo.jpg'));
        $this->thumbnailDisk->assertExists((string) $media->thumbnail_path);

        foreach ($staleCachePaths as $path) {
            $this->cacheDisk->assertMissing($path);
        }
    }

    public function test_keep_source_flag_preserves_original(): void
    {
        $media = $this->createLocalMedia();

        $result = app(MigrateMediaToS3::class)->execute($media, deleteSource: false);

        $this->assertTrue($result->isMigrated());
        $this->assertFalse($result->localDeleted);
        $this->localDisk->assertExists('images/photo.jpg');
        $this->s3Disk->assertExists('images/photo.jpg');
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 's3']);
    }

    public function test_media_already_on_s3_is_skipped(): void
    {
        $media = $this->createMediaRow('s3', 'images/photo.jpg');

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('уже на S3', (string) $result->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 's3']);
    }

    public function test_missing_source_is_reported(): void
    {
        $media = $this->createMediaRow('public', 'ghosts/missing.jpg');

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('оригинал отсутствует', (string) $result->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->assertCount(0, $this->s3Disk->allFiles());
    }

    public function test_upload_failure_keeps_source_and_record(): void
    {
        Log::spy();
        $media = $this->createLocalMedia();

        $failingS3 = Mockery::mock(Filesystem::class);
        $failingS3->shouldReceive('exists')->once()->andReturnFalse();
        $failingS3->shouldReceive('writeStream')->once()->andThrow(new RuntimeException('s3 unavailable'));

        Storage::shouldReceive('disk')->with('s3')->andReturn($failingS3);
        Storage::shouldReceive('disk')->with('public')->andReturn($this->localDisk);
        Storage::shouldReceive('disk')->with('thumbnails')->andReturn($this->thumbnailDisk);
        Storage::shouldReceive('disk')->with('image_cache')->andReturn($this->cacheDisk);

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isFailed());
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $media->file_path);
        $this->thumbnailDisk->assertExists((string) $media->thumbnail_path);
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_verification_failure_removes_broken_upload_and_keeps_source(): void
    {
        Log::spy();
        $media = $this->createLocalMedia();

        $lyingS3 = Mockery::mock(Filesystem::class);
        $lyingS3->shouldReceive('exists')->once()->andReturnFalse();
        $lyingS3->shouldReceive('writeStream')->once()->andReturnTrue();
        $lyingS3->shouldReceive('size')->once()->andReturn(999999);
        $lyingS3->shouldReceive('delete')->once()->with('images/photo.jpg')->andReturnTrue();

        Storage::shouldReceive('disk')->with('s3')->andReturn($lyingS3);
        Storage::shouldReceive('disk')->with('public')->andReturn($this->localDisk);
        Storage::shouldReceive('disk')->with('thumbnails')->andReturn($this->thumbnailDisk);
        Storage::shouldReceive('disk')->with('image_cache')->andReturn($this->cacheDisk);

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('проверка', (string) $result->reason);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 'public']);
        $this->localDisk->assertExists((string) $media->file_path);
    }

    public function test_identical_s3_file_is_reused_without_reupload(): void
    {
        $media = $this->createLocalMedia();
        $this->s3Disk->put('images/photo.jpg', $this->localDisk->get((string) $media->file_path));

        $s3Spy = Mockery::mock($this->s3Disk)->makePartial();
        $s3Spy->shouldNotReceive('writeStream');

        Storage::shouldReceive('disk')->with('s3')->andReturn($s3Spy);
        Storage::shouldReceive('disk')->with('public')->andReturn($this->localDisk);
        Storage::shouldReceive('disk')->with('thumbnails')->andReturn($this->thumbnailDisk);
        Storage::shouldReceive('disk')->with('image_cache')->andReturn($this->cacheDisk);

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isMigrated());
        $this->assertDatabaseHas('media', ['id' => $media->id, 'disk' => 's3']);
        $this->localDisk->assertMissing('images/photo.jpg');
        $this->s3Disk->assertExists('images/photo.jpg');
    }

    public function test_non_image_file_is_migrated_and_mime_is_detected(): void
    {
        $this->localDisk->put('files/doc.pdf', '%PDF-1.4 test');
        $media = $this->createMediaRow('public', 'files/doc.pdf', mime: null);

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isMigrated());

        $media->refresh();
        $this->assertSame('s3', $media->disk);
        $this->assertSame('application/pdf', $media->mime_type);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
        $this->s3Disk->assertExists('files/doc.pdf');
    }

    public function test_null_mime_image_gets_metadata(): void
    {
        UploadedFile::fake()->image('photo.jpg', 800, 600)->storeAs('images', 'photo.jpg', 'public');

        $media = new Media([
            'file_path' => 'images/photo.jpg',
            'disk' => 'public',
            'mime_type' => null,
            'width' => null,
            'height' => null,
        ]);
        $media->saveQuietly();

        $result = app(MigrateMediaToS3::class)->execute($media);

        $this->assertTrue($result->isMigrated());

        $media->refresh();
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(800, $media->width);
        $this->assertSame(600, $media->height);
    }

    public function test_derivative_and_unknown_disks_are_skipped(): void
    {
        $derivative = $this->createMediaRow('thumbnails', 'thumbs/thumb.webp');
        $unknown = $this->createMediaRow('legacy_ftp', 'images/photo.jpg');

        $this->assertTrue(app(MigrateMediaToS3::class)->execute($derivative)->isSkipped());
        $this->assertTrue(app(MigrateMediaToS3::class)->execute($unknown)->isSkipped());
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
            'collection' => 'gallery',
        ]);

        $media->refresh();

        $this->assertNotNull($media->thumbnail_path, 'Test precondition: thumbnail must exist');

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
