<?php

namespace Tests\Feature\Actions;

use App\Actions\Media\CheckMediaIntegrity;
use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('yandex_disk');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_valid_media_returns_valid(): void
    {
        $media = $this->createCompleteLocalMedia();

        $result = app(CheckMediaIntegrity::class)->check($media);

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->isMissingOriginal());
        $this->assertFalse($result->isMissingThumbnail());
        $this->assertFalse($result->isMissingImageCache());
        $this->assertFalse($result->isMetadataMismatch());
        $this->assertFalse($result->isError());
    }

    public function test_missing_original_returns_missing_original(): void
    {
        $media = Media::query()->create([
            'file_path' => 'images/missing.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media);

        $this->assertTrue($result->isMissingOriginal());
    }

    public function test_missing_thumbnail_returns_missing_thumbnail(): void
    {
        $media = $this->createLocalMediaWithOriginal('images/photo.jpg');

        $media->update([
            'thumbnail_path' => null,
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'file_size' => 102400,
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media->fresh());

        $this->assertTrue($result->isMissingThumbnail());
    }

    public function test_missing_image_cache_returns_missing_image_cache(): void
    {
        $media = $this->createLocalMediaWithOriginal('images/photo.jpg');

        $thumbPath = 'images/photo_thumb.webp';
        Storage::disk('thumbnails')->put($thumbPath, 'thumb-content');

        $media->update([
            'thumbnail_path' => $thumbPath,
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'file_size' => 102400,
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media->fresh());

        $this->assertTrue($result->isMissingImageCache());
    }

    public function test_metadata_mismatch_returns_status(): void
    {
        $media = $this->createLocalMediaWithOriginal('images/photo.jpg');

        $thumbPath = 'images/photo_thumb.webp';
        Storage::disk('thumbnails')->put($thumbPath, 'thumb-content');

        $cache = app(ImageCacheService::class);
        Storage::disk('image_cache')->put($cache->relativePath($media, 'display'), 'cache-display');
        Storage::disk('image_cache')->put($cache->relativePath($media, 'lightbox'), 'cache-lightbox');

        $media->update([
            'thumbnail_path' => $thumbPath,
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'file_size' => 0,
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media->fresh());

        $this->assertTrue($result->isMetadataMismatch());
        $this->assertStringContainsString('file_size mismatch', (string) $result->detail);
    }

    public function test_non_image_without_metadata_returns_metadata_mismatch(): void
    {
        Storage::disk('public')->put('docs/file.pdf', 'pdf-content');

        $media = Media::query()->create([
            'file_path' => 'docs/file.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => null,
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media);

        $this->assertTrue($result->isMetadataMismatch());
        $this->assertSame('file_size is missing', $result->detail);
    }

    public function test_image_with_zero_dimensions_returns_metadata_mismatch(): void
    {
        $media = $this->createLocalMediaWithOriginal('images/photo.jpg');

        $thumbPath = 'images/photo_thumb.webp';
        Storage::disk('thumbnails')->put($thumbPath, 'thumb-content');

        $cache = app(ImageCacheService::class);
        Storage::disk('image_cache')->put($cache->relativePath($media, 'display'), 'cache-display');
        Storage::disk('image_cache')->put($cache->relativePath($media, 'lightbox'), 'cache-lightbox');

        $media->update([
            'thumbnail_path' => $thumbPath,
            'mime_type' => 'image/jpeg',
            'width' => 0,
            'height' => 0,
            'file_size' => 102400,
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media->fresh());

        $this->assertTrue($result->isMetadataMismatch());
        $this->assertStringContainsString('invalid dimensions', (string) $result->detail);
    }

    public function test_non_image_with_file_size_is_valid(): void
    {
        Storage::disk('public')->put('docs/file.pdf', 'pdf-content');

        $media = Media::query()->create([
            'file_path' => 'docs/file.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => 51200,
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media);

        $this->assertTrue($result->isValid());
    }

    public function test_remote_disk_with_existing_original_is_valid(): void
    {
        $media = Media::query()->create([
            'file_path' => 'images/remote.jpg',
            'disk' => 'yandex_disk',
            'mime_type' => 'image/jpeg',
            'width' => 1200,
            'height' => 800,
            'file_size' => 204800,
        ]);

        Storage::disk('yandex_disk')->put('images/remote.jpg', str_repeat('x', 204800));

        $thumbPath = 'images/remote_thumb.webp';
        Storage::disk('thumbnails')->put($thumbPath, 'thumb-content');

        $cache = app(ImageCacheService::class);
        Storage::disk('image_cache')->put($cache->relativePath($media, 'display'), 'cache-display');
        Storage::disk('image_cache')->put($cache->relativePath($media, 'lightbox'), 'cache-lightbox');

        $media->update(['thumbnail_path' => $thumbPath]);

        $result = app(CheckMediaIntegrity::class)->check($media->fresh());

        $this->assertTrue($result->isValid());
    }

    public function test_empty_disk_treated_as_missing_original(): void
    {
        $media = Media::query()->create([
            'file_path' => 'images/photo.jpg',
            'disk' => '',
            'mime_type' => 'image/jpeg',
        ]);

        $result = app(CheckMediaIntegrity::class)->check($media);

        $this->assertTrue($result->isMissingOriginal());
    }

    protected function createCompleteLocalMedia(): Media
    {
        $media = $this->createLocalMediaWithOriginal('images/complete.jpg');

        $thumbPath = 'images/complete_thumb.webp';
        Storage::disk('thumbnails')->put($thumbPath, 'thumb-content');

        $cache = app(ImageCacheService::class);
        Storage::disk('image_cache')->put($cache->relativePath($media, 'display'), 'cache-display');
        Storage::disk('image_cache')->put($cache->relativePath($media, 'lightbox'), 'cache-lightbox');

        $media->update([
            'thumbnail_path' => $thumbPath,
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'file_size' => 102400,
        ]);

        return $media->fresh();
    }

    protected function createLocalMediaWithOriginal(string $path): Media
    {
        Storage::disk('public')->put($path, str_repeat('x', 102400));

        return Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);
    }
}
