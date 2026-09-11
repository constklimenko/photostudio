<?php

namespace Tests\Unit\Models;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_get_url_returns_null_when_no_file_path(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => '',
        ]);

        $this->assertNull($media->getUrl());
    }

    public function test_get_url_returns_proxy_route_for_remote_disk(): void
    {
        config(['filesystems.disks.yandex_disk.remote' => true]);

        $media = Media::query()->create([
            'disk' => 'yandex_disk',
            'file_path' => 'photos/test.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertSame(
            route('media.original', ['media' => $media->getKey()]),
            $media->getUrl(),
        );
    }

    public function test_get_url_returns_proxy_route_for_local_disk(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'images/test.jpg',
        ]);

        $this->assertSame(
            route('media.original', ['media' => $media->getKey()]),
            $media->getUrl(),
        );
    }

    public function test_get_thumbnail_url_falls_back_to_url_when_no_thumbnail_path(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'images/test.jpg',
            'thumbnail_path' => null,
        ]);

        $this->assertSame($media->getUrl(), $media->getThumbnailUrl());
    }

    public function test_get_thumbnail_url_returns_thumbnail_route_when_set(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'images/test.jpg',
            'thumbnail_path' => 'images/test_thumb.webp',
        ]);

        $this->assertSame(
            route('media.thumbnail', ['media' => $media->getKey()]),
            $media->getThumbnailUrl(),
        );
    }

    public function test_get_url_never_returns_raw_storage_path(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'images/test.jpg',
            'thumbnail_path' => 'images/test_thumb.webp',
        ]);

        $this->assertStringNotContainsString('/storage/', $media->getUrl());
        $this->assertStringNotContainsString('/storage/', $media->getThumbnailUrl());
    }

    public function test_get_thumbnail_url_returns_null_when_no_file_path(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => '',
        ]);

        $this->assertNull($media->getUrl());
        $this->assertNull($media->getThumbnailUrl());
    }

    public function test_get_display_url_returns_null_for_non_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => 'text/plain',
        ]);

        $this->assertNull($media->getDisplayUrl());
    }

    public function test_get_display_url_returns_route_for_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'images/test.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $url = $media->getDisplayUrl();

        $this->assertSame(route('media.display', ['media' => $media->getKey()]), $url);
    }

    public function test_get_lightbox_url_returns_null_for_non_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => 'text/plain',
        ]);

        $this->assertNull($media->getLightboxUrl());
    }

    public function test_get_lightbox_url_returns_route_for_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'images/test.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $url = $media->getLightboxUrl();

        $this->assertSame(route('media.lightbox', ['media' => $media->getKey()]), $url);
    }

    public function test_is_remote_disk_returns_true_when_configured(): void
    {
        config(['filesystems.disks.yandex_disk.remote' => true]);

        $media = Media::query()->create([
            'disk' => 'yandex_disk',
            'file_path' => 'test.jpg',
        ]);

        $this->assertTrue($media->isRemoteDisk('yandex_disk'));
    }

    public function test_is_remote_disk_returns_false_for_local_disk(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'test.jpg',
        ]);

        $this->assertFalse($media->isRemoteDisk('public'));
    }

    public function test_fillable_attributes(): void
    {
        $media = Media::query()->create([
            'title' => 'Test Title',
            'alt_text' => 'Alt text',
            'disk' => 'public',
            'file_path' => 'images/test.jpg',
            'thumbnail_path' => 'images/test_thumb.webp',
            'mime_type' => 'image/jpeg',
            'width' => 1920,
            'height' => 1080,
            'file_size' => 512000,
            'collection' => 'gallery',
        ]);

        $this->assertSame('Test Title', $media->title);
        $this->assertSame('Alt text', $media->alt_text);
        $this->assertSame('gallery', $media->collection);
        $this->assertSame(1920, $media->width);
        $this->assertSame(1080, $media->height);
        $this->assertSame(512000, $media->file_size);
    }

    public function test_is_remote_disk_defaults_to_false_for_unknown_disk(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'test.jpg',
        ]);

        $this->assertFalse($media->isRemoteDisk('nonexistent_disk'));
    }
}
