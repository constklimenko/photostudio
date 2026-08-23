<?php

namespace Tests\Unit\Services;

use App\Models\Media;
use App\Services\ImageCacheService;
use App\Services\MediaProcessor;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MediaProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected MediaProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new MediaProcessor;
        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_fills_metadata_for_image(): void
    {
        $media = $this->makeMedia($this->storeJpeg(1920, 1080));

        $this->assertTrue($this->processor->process($media));

        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(1920, $media->width);
        $this->assertSame(1080, $media->height);
        $this->assertNotNull($media->file_size);
        $this->assertGreaterThan(0, (int) $media->file_size);
    }

    public function test_fills_metadata_for_non_image_without_thumbnail(): void
    {
        Storage::disk('public')->put('documents/readme.txt', 'plain text');
        $media = $this->makeMedia('documents/readme.txt');

        $this->assertTrue($this->processor->process($media));

        $media->refresh();

        $this->assertSame('text/plain', $media->mime_type);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
        $this->assertNull($media->thumbnail_path);
        $this->assertNotNull($media->file_size);
    }

    public function test_generates_webp_thumbnail_max_400px_for_landscape(): void
    {
        $media = $this->makeMedia($this->storeJpeg(800, 400));

        $this->assertTrue($this->processor->process($media));
        $media->refresh();

        $this->assertNotNull($media->thumbnail_path);
        $this->assertSame('images/test_thumb.webp', $media->thumbnail_path);

        $thumbDisk = Storage::disk('thumbnails');
        $thumbDisk->assertExists($media->thumbnail_path);

        [$width, $height] = getimagesize($thumbDisk->path($media->thumbnail_path));

        $this->assertLessThanOrEqual(400, $width);
        $this->assertLessThanOrEqual(400, $height);
    }

    public function test_generates_webp_thumbnail_max_400px_for_portrait(): void
    {
        $path = $this->storeJpeg(400, 800, 'images/portrait.jpg');
        $media = $this->makeMedia($path);

        $this->assertTrue($this->processor->process($media));
        $media->refresh();

        [$width, $height] = getimagesize(Storage::disk('thumbnails')->path($media->thumbnail_path));

        $this->assertLessThanOrEqual(400, $width);
        $this->assertLessThanOrEqual(400, $height);
    }

    public function test_thumbnail_at_disk_root_when_original_has_no_directory(): void
    {
        $path = $this->storeJpeg(600, 600, 'top.jpg');
        $media = $this->makeMedia($path);

        $this->assertTrue($this->processor->process($media));
        $media->refresh();

        $this->assertSame('top_thumb.webp', $media->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);
    }

    public function test_thumbnail_path_is_deterministic(): void
    {
        $this->assertSame('images/a_thumb.webp', $this->processor->thumbnailPath('images/a.jpg'));
        $this->assertSame('b_thumb.webp', $this->processor->thumbnailPath('b.png'));
        $this->assertSame('x/y/c_thumb.webp', $this->processor->thumbnailPath('/x/y/c.webp'));
    }

    public function test_warms_display_and_lightbox_variants_from_same_temp_file(): void
    {
        $media = $this->makeMedia($this->storeJpeg(2000, 1000));

        $this->assertTrue($this->processor->process($media));
        $media->refresh();

        $service = new ImageCacheService;
        $cacheDisk = Storage::disk('image_cache');

        foreach ([ImageCacheService::TIER_DISPLAY => 800, ImageCacheService::TIER_LIGHTBOX => 1600] as $tier => $maxSide) {
            $path = $service->relativePath($media, $tier);

            $cacheDisk->assertExists($path);

            [$width, $height] = getimagesize($cacheDisk->path($path));

            $this->assertLessThanOrEqual($maxSide, max($width, $height));
        }
    }

    public function test_variant_write_failure_marks_processing_incomplete(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $realPublicDisk = Storage::disk('public');
        $realThumbDisk = Storage::disk('thumbnails');
        $path = $this->storeJpeg(600, 600);

        $cacheMock = Mockery::mock(Filesystem::class);
        $cacheMock->shouldReceive('exists')->andReturnFalse();
        $cacheMock->shouldReceive('put')
            ->andThrow(new RuntimeException('cache write failed'));

        Storage::shouldReceive('disk')->with('public')->andReturn($realPublicDisk);
        Storage::shouldReceive('disk')->with('thumbnails')->andReturn($realThumbDisk);
        Storage::shouldReceive('disk')->with('image_cache')->andReturn($cacheMock);

        $media = $this->makeMedia($path);

        $this->assertFalse($this->processor->process($media));

        $media->refresh();

        $this->assertNotNull($media->thumbnail_path);
    }

    public function test_repeated_processing_is_noop(): void
    {
        $media = $this->makeMedia($this->storeJpeg(800, 600));

        $this->processor->process($media);
        $media->refresh();

        $thumbDisk = Storage::disk('thumbnails');
        $cacheDisk = Storage::disk('image_cache');
        $thumbnailBefore = $thumbDisk->get($media->thumbnail_path);
        $displayPath = (new ImageCacheService)->relativePath($media, ImageCacheService::TIER_DISPLAY);
        $displayBefore = $cacheDisk->get($displayPath);
        $updatedAtBefore = $media->updated_at->toString();
        $metadataBefore = $media->only(['mime_type', 'width', 'height', 'file_size']);

        sleep(1);
        $this->assertTrue($this->processor->process($media));

        $media->refresh();

        $this->assertSame($metadataBefore, $media->only(['mime_type', 'width', 'height', 'file_size']));
        $this->assertSame($thumbnailBefore, $thumbDisk->get($media->thumbnail_path));
        $this->assertSame($displayBefore, $cacheDisk->get($displayPath));
        $this->assertCount(1, $thumbDisk->allFiles());
        $this->assertCount(2, $cacheDisk->allFiles());
        $this->assertSame($updatedAtBefore, $media->updated_at->toString());
    }

    public function test_repeated_processing_succeeds_without_original_when_complete(): void
    {
        $path = $this->storeJpeg(800, 600);
        $media = $this->makeMedia($path);

        $this->processor->process($media);
        $media->refresh();

        Storage::disk('public')->delete($path);

        $this->assertTrue($this->processor->process($media));
        $this->assertNotNull($media->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);
    }

    public function test_missing_thumbnail_file_is_regenerated_without_force(): void
    {
        $media = $this->makeMedia($this->storeJpeg(800, 600));

        $this->processor->process($media);
        $media->refresh();

        Storage::disk('thumbnails')->delete($media->thumbnail_path);

        $this->assertTrue($this->processor->process($media));
        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);
    }

    public function test_force_regenerates_existing_thumbnail(): void
    {
        $media = $this->makeMedia($this->storeJpeg(800, 600));

        $this->processor->process($media);
        $media->refresh();

        $oldPath = $media->thumbnail_path;
        Storage::disk('thumbnails')->delete($oldPath);
        $media->thumbnail_path = $oldPath.'-stale';
        $media->saveQuietly();

        $this->assertTrue($this->processor->process($media, force: true));
        $media->refresh();

        $this->assertSame($oldPath, $media->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($oldPath);
    }

    public function test_missing_original_returns_false_and_keeps_data(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $media = $this->makeMedia('images/nonexistent.jpg', [
            'mime_type' => 'image/jpeg',
            'width' => 100,
            'height' => 100,
            'thumbnail_path' => null,
        ]);

        $this->assertFalse($this->processor->process($media));

        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(100, $media->width);
        $this->assertNull($media->thumbnail_path);
    }

    public function test_unreadable_stream_returns_false_and_keeps_data(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $processor = new class extends MediaProcessor
        {
            protected function spoolToTempFile(Filesystem $disk, string $path): ?string
            {
                return null;
            }
        };

        $media = $this->makeMedia($this->storeJpeg(300, 300));

        $this->assertFalse($processor->process($media));

        $media->refresh();

        $this->assertNull($media->mime_type);
        $this->assertNull($media->thumbnail_path);
    }

    public function test_corrupted_image_keeps_known_metadata_but_fails(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $full = $this->jpegBytes(400, 300);
        Storage::disk('public')->put('images/broken.jpg', substr($full, 0, 64));

        $media = $this->makeMedia('images/broken.jpg');

        $this->assertFalse($this->processor->process($media));

        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertNotNull($media->file_size);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
        $this->assertNull($media->thumbnail_path);
    }

    public function test_storage_error_is_caught_and_logged(): void
    {
        Log::shouldReceive('error')->once();

        $mock = Mockery::mock(Filesystem::class);
        $mock->shouldReceive('exists')
            ->andThrow(new RuntimeException('storage unavailable'));

        Storage::shouldReceive('disk')->andReturn($mock);

        $media = $this->makeMedia('images/any.jpg');

        $this->assertFalse($this->processor->process($media));
    }

    public function test_thumbnail_write_failure_keeps_metadata(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $realPublicDisk = Storage::disk('public');
        $path = $this->storeJpeg(500, 500);

        $thumbMock = Mockery::mock(Filesystem::class);
        $thumbMock->shouldReceive('exists')->andReturnFalse();
        $thumbMock->shouldReceive('put')
            ->andThrow(new RuntimeException('write failed'));

        $cacheMock = Mockery::mock(Filesystem::class);
        $cacheMock->shouldReceive('exists')->andReturnFalse();
        $cacheMock->shouldReceive('put')->andReturnTrue();
        $cacheMock->shouldReceive('allFiles')->andReturn([]);

        Storage::shouldReceive('disk')->with('thumbnails')->andReturn($thumbMock);
        Storage::shouldReceive('disk')->with('public')->andReturn($realPublicDisk);
        Storage::shouldReceive('disk')->with('image_cache')->andReturn($cacheMock);

        $media = $this->makeMedia($path);

        $this->assertFalse($this->processor->process($media));

        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(500, $media->width);
        $this->assertSame(500, $media->height);
        $this->assertNull($media->thumbnail_path);
    }

    protected function makeMedia(string $filePath, array $attributes = []): Media
    {
        $media = Media::factory()->make(array_merge([
            'file_path' => $filePath,
            'disk' => 'public',
            'mime_type' => null,
            'width' => null,
            'height' => null,
            'file_size' => null,
            'thumbnail_path' => null,
        ], $attributes));

        $media->saveQuietly();

        return $media;
    }

    protected function storeJpeg(int $width, int $height, string $path = 'images/test.jpg'): string
    {
        Storage::disk('public')->put($path, $this->jpegBytes($width, $height));

        return $path;
    }

    protected function jpegBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 120, 40, 200));

        ob_start();
        imagejpeg($image, quality: 85);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
