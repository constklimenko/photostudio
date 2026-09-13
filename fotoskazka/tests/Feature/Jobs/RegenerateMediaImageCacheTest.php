<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RegenerateMediaImageCache;
use App\Models\Media;
use App\Services\ImageCacheService;
use App\Services\MediaProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegenerateMediaImageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_handle_regenerates_missing_cache_variants(): void
    {
        $media = $this->processedMedia(2000, 1000);
        $cache = new ImageCacheService;

        foreach (array_keys($cache->tiers()) as $tier) {
            Storage::disk('image_cache')->delete($cache->relativePath($media, (string) $tier));
        }

        $this->runJob($media->id);

        foreach ([ImageCacheService::TIER_DISPLAY => 800, ImageCacheService::TIER_LIGHTBOX => 1600] as $tier => $maxSide) {
            $path = $cache->relativePath($media, $tier);
            Storage::disk('image_cache')->assertExists($path);

            [$width, $height] = getimagesize(Storage::disk('image_cache')->path($path));
            $this->assertLessThanOrEqual($maxSide, max($width, $height));
        }
    }

    public function test_handle_touches_only_cache_not_thumbnail_or_metadata(): void
    {
        $media = $this->processedMedia();
        $media->refresh();

        $thumbDisk = Storage::disk('thumbnails');
        $thumbnailBefore = $thumbDisk->get($media->thumbnail_path);
        $metadataBefore = $media->only(['mime_type', 'width', 'height', 'file_size', 'thumbnail_path']);

        $cache = new ImageCacheService;
        $cache->forget($media);
        $this->runJob($media->id);

        $media->refresh();

        $this->assertSame($metadataBefore, $media->only(['mime_type', 'width', 'height', 'file_size', 'thumbnail_path']));
        $this->assertSame($thumbnailBefore, $thumbDisk->get($media->thumbnail_path));
    }

    public function test_force_rewrites_existing_cache_variants(): void
    {
        $media = $this->processedMedia();
        $cache = new ImageCacheService;

        $displayPath = $cache->relativePath($media, ImageCacheService::TIER_DISPLAY);
        Storage::disk('image_cache')->put($displayPath, 'stale-data');

        $this->runJob($media->id, force: true);

        Storage::disk('image_cache')->assertExists($displayPath);
        $this->assertNotSame('stale-data', Storage::disk('image_cache')->get($displayPath));
    }

    public function test_missing_cached_variant_is_regenerated_without_force(): void
    {
        $media = $this->processedMedia();
        $cache = new ImageCacheService;

        $displayPath = $cache->relativePath($media, ImageCacheService::TIER_DISPLAY);
        Storage::disk('image_cache')->put($displayPath, 'stale-data');
        Storage::disk('image_cache')->delete($cache->relativePath($media, ImageCacheService::TIER_LIGHTBOX));

        $this->runJob($media->id);

        Storage::disk('image_cache')->assertExists($displayPath);
        Storage::disk('image_cache')->assertExists($cache->relativePath($media, ImageCacheService::TIER_LIGHTBOX));
    }

    public function test_existing_variant_is_kept_without_force_even_if_stale(): void
    {
        $media = $this->processedMedia();
        $cache = new ImageCacheService;

        $displayPath = $cache->relativePath($media, ImageCacheService::TIER_DISPLAY);
        Storage::disk('image_cache')->put($displayPath, 'stale-data');

        $this->runJob($media->id);

        $this->assertSame('stale-data', Storage::disk('image_cache')->get($displayPath));
    }

    public function test_missing_media_is_ignored_without_failure(): void
    {
        Log::shouldReceive('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'RegenerateMediaImageCache: media record not found.'
                && $context['media_id'] === 99999,
        );

        $this->runJob(99999);

        $this->assertDatabaseCount('media', 0);
    }

    public function test_missing_original_completes_job_without_failure(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $media = $this->makeMedia('images/never-uploaded.jpg');

        $this->runJob($media->id);

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->assertCount(0, Storage::disk('image_cache')->allFiles());
    }

    public function test_retry_configuration_is_sane(): void
    {
        $job = new RegenerateMediaImageCache(1);

        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertTrue($job->afterCommit);
        $this->assertSame([30, 120], $job->backoff());
    }

    protected function runJob(int $mediaId, bool $force = false): void
    {
        (new RegenerateMediaImageCache($mediaId, $force))->handle(app(MediaProcessor::class));
    }

    protected function processedMedia(int $width = 1200, int $height = 800): Media
    {
        $this->storeJpeg($width, $height);
        $media = $this->makeMedia('images/photo.jpg');

        app(MediaProcessor::class)->process($media);

        return $media;
    }

    protected function makeMedia(string $filePath): Media
    {
        $media = Media::factory()->make([
            'file_path' => $filePath,
            'disk' => 'public',
        ]);

        $media->saveQuietly();

        return $media;
    }

    protected function storeJpeg(int $width, int $height, string $path = 'images/photo.jpg'): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 120, 40, 200));

        ob_start();
        imagejpeg($image, quality: 85);
        imagedestroy($image);

        Storage::disk('public')->put($path, (string) ob_get_clean());
    }
}
