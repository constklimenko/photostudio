<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Media;
use App\Models\User;
use App\Services\ImageCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaImageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.image_cache.max_size_mb' => 2048]);

        Storage::fake('public');
        Storage::fake('image_cache');
        Storage::fake('thumbnails');
    }

    public function test_display_generates_cached_png_on_first_request(): void
    {
        $media = $this->createImageMedia(1200, 800);

        $response = $this->get(route('media.display', ['media' => $media]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');

        $cacheDisk = Storage::disk('image_cache');

        $cached = $cacheDisk->allFiles();

        $this->assertCount(1, $cached);
        $this->assertStringStartsWith('display/', $cached[0]);

        $image = imagecreatefromstring($cacheDisk->get($cached[0]));

        $this->assertNotFalse($image);
        $this->assertLessThanOrEqual(800, imagesx($image));
    }

    public function test_lightbox_generates_cached_png_on_first_request(): void
    {
        $media = $this->createImageMedia(2400, 1600);

        $response = $this->get(route('media.lightbox', ['media' => $media]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeaderContains('Cache-Control', 'immutable');

        $cacheDisk = Storage::disk('image_cache');

        $cached = $cacheDisk->allFiles();

        $this->assertCount(1, $cached);
        $this->assertStringStartsWith('lightbox/', $cached[0]);

        $image = imagecreatefromstring($cacheDisk->get($cached[0]));

        $this->assertNotFalse($image);
        $this->assertLessThanOrEqual(1600, imagesx($image));
    }

    public function test_second_request_reuses_cached_file(): void
    {
        $media = $this->createImageMedia(1000, 1000);

        $path = app(ImageCacheService::class)->relativePath($media, ImageCacheService::TIER_DISPLAY);

        Storage::disk('image_cache')->put($path, 'pre-cached-marker');

        $response = $this->get(route('media.display', ['media' => $media]));

        $response->assertStatus(200);
        $this->assertSame('pre-cached-marker', Storage::disk('image_cache')->get($path));
    }

    public function test_display_returns_404_when_source_file_missing(): void
    {
        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'public',
            'file_path' => 'albums/missing.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->get(route('media.display', ['media' => $media]))->assertNotFound();
    }

    public function test_returns_404_for_non_image(): void
    {
        Storage::disk('public')->put('docs/readme.txt', 'hello');

        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'public',
            'file_path' => 'docs/readme.txt',
        ]);

        $this->get(route('media.display', ['media' => $media]))->assertNotFound();
        $this->get(route('media.lightbox', ['media' => $media]))->assertNotFound();
    }

    public function test_download_requires_authentication(): void
    {
        $bytes = $this->makeJpegBytes(800, 600);
        Storage::disk('public')->put('albums/photo.jpg', $bytes);

        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'public',
            'file_path' => 'albums/photo.jpg',
        ]);

        $this->get(route('media.download', ['media' => $media]))
            ->assertRedirect(route('login'));
    }

    public function test_download_streams_original_as_attachment(): void
    {
        $bytes = $this->makeJpegBytes(800, 600);
        Storage::disk('public')->put('albums/photo.jpg', $bytes);

        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'public',
            'file_path' => 'albums/photo.jpg',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('media.download', ['media' => $media]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeaderContains('Content-Disposition', 'attachment');
        $response->assertHeaderContains('Content-Disposition', 'photo.jpg');
        $this->assertSame($bytes, $response->streamedContent());
    }

    public function test_model_urls_point_to_derivative_routes(): void
    {
        Storage::disk('public')->put('albums/photo.jpg', $this->makeJpegBytes(800, 600));

        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'public',
            'file_path' => 'albums/photo.jpg',
        ]);

        $this->assertSame(
            route('media.display', ['media' => $media->getKey()]),
            $media->getDisplayUrl(),
        );
        $this->assertSame(
            route('media.lightbox', ['media' => $media->getKey()]),
            $media->getLightboxUrl(),
        );
    }

    public function test_purge_removes_oldest_files_over_limit(): void
    {
        config(['filesystems.image_cache.max_size_mb' => 0.7]);

        $disk = Storage::disk('image_cache');
        $service = app(ImageCacheService::class);

        foreach (['old-1.png', 'old-2.png'] as $i => $file) {
            $disk->put("lightbox/{$file}", str_repeat('a', 600 * 1024));
            touch($disk->path("lightbox/{$file}"), time() - 300 + $i);
        }

        $disk->put('lightbox/new.png', str_repeat('b', 100 * 1024));
        touch($disk->path('lightbox/new.png'), time());

        $freed = $service->purgeToLimit();

        $this->assertGreaterThan(0, $freed);
        $this->assertFalse($disk->exists('lightbox/old-1.png'));
        $this->assertTrue($disk->exists('lightbox/old-2.png'));
        $this->assertTrue($disk->exists('lightbox/new.png'));
    }

    public function test_prune_command_clears_all_with_option(): void
    {
        Storage::disk('image_cache')->put('display/x.png', 'data');

        $this->artisan('media:prune-image-cache', ['--all' => true])
            ->expectsOutputToContain('Кэш полностью очищен')
            ->assertSuccessful();

        $this->assertSame([], Storage::disk('image_cache')->allFiles());
    }

    protected function createImageMedia(int $width, int $height): Media
    {
        Storage::disk('public')->put('albums/photo.jpg', $this->makeJpegBytes($width, $height));

        return Media::query()->create([
            'album_id' => null,
            'disk' => 'public',
            'file_path' => 'albums/photo.jpg',
        ]);
    }

    protected function makeJpegBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 90, 30, 180));

        ob_start();
        imagejpeg($image, quality: 85);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
