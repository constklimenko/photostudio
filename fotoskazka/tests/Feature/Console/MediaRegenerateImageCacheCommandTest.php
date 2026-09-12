<?php

namespace Tests\Feature\Console;

use App\Jobs\RegenerateMediaImageCache;
use App\Models\Media;
use App\Services\ImageCacheService;
use App\Services\MediaProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaRegenerateImageCacheCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_dispatches_jobs_for_media_missing_cache_variants(): void
    {
        Queue::fake();

        $media = $this->processedMedia();

        $cache = new ImageCacheService;
        foreach (array_keys($cache->tiers()) as $tier) {
            Storage::disk('image_cache')->delete($cache->relativePath($media, (string) $tier));
        }

        $this->artisan('media:regenerate-image-cache')
            ->expectsOutputToContain('Found 1 media to regenerate.')
            ->expectsOutputToContain('Dispatched 1 job(s)')
            ->assertSuccessful();

        Queue::assertPushed(RegenerateMediaImageCache::class, 1);
        Queue::assertPushed(RegenerateMediaImageCache::class, function (RegenerateMediaImageCache $job) use ($media): bool {
            return $job->mediaId === $media->id && $job->force === false;
        });
    }

    public function test_dry_run_dispatches_nothing(): void
    {
        Queue::fake();

        $media = $this->processedMedia();

        $cache = new ImageCacheService;
        Storage::disk('image_cache')->delete($cache->relativePath($media, ImageCacheService::TIER_DISPLAY));

        $this->artisan('media:regenerate-image-cache', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        Queue::assertNotPushed(RegenerateMediaImageCache::class);
    }

    public function test_force_dispatches_for_all_complete_media(): void
    {
        Queue::fake();

        $this->processedMedia();

        $this->artisan('media:regenerate-image-cache', ['--force' => true])
            ->expectsOutputToContain('Found 1 media to regenerate.')
            ->assertSuccessful();

        Queue::assertPushed(RegenerateMediaImageCache::class, 1);
        Queue::assertPushed(RegenerateMediaImageCache::class, fn (RegenerateMediaImageCache $job): bool => $job->force === true);
    }

    public function test_skips_complete_media_without_force(): void
    {
        Queue::fake();

        $this->processedMedia();

        $this->artisan('media:regenerate-image-cache')
            ->expectsOutputToContain('No media found matching criteria.')
            ->assertSuccessful();

        Queue::assertNotPushed(RegenerateMediaImageCache::class);
    }

    public function test_non_image_media_are_never_selected(): void
    {
        Queue::fake();

        Media::query()->create([
            'disk' => 'public',
            'file_path' => 'documents/readme.txt',
            'mime_type' => 'text/plain',
        ]);

        $this->artisan('media:regenerate-image-cache', ['--force' => true])
            ->expectsOutputToContain('No media found matching criteria.')
            ->assertSuccessful();

        Queue::assertNotPushed(RegenerateMediaImageCache::class);
    }

    public function test_limit_restricts_dispatched_jobs(): void
    {
        Queue::fake();

        $mediaList = [$this->processedMedia('images/a.jpg'), $this->processedMedia('images/b.jpg'), $this->processedMedia('images/c.jpg')];

        $cache = new ImageCacheService;
        foreach ($mediaList as $media) {
            Storage::disk('image_cache')->delete($cache->relativePath($media, ImageCacheService::TIER_LIGHTBOX));
        }

        $this->artisan('media:regenerate-image-cache', ['--limit' => 2])
            ->expectsOutputToContain('Found 2 media to regenerate.')
            ->assertSuccessful();

        Queue::assertPushed(RegenerateMediaImageCache::class, 2);
    }

    public function test_specific_id_restricts_selection(): void
    {
        Queue::fake();

        $first = $this->processedMedia('images/a.jpg');
        $second = $this->processedMedia('images/b.jpg');

        $cache = new ImageCacheService;
        $cache->forget($first);
        $cache->forget($second);

        $this->artisan('media:regenerate-image-cache', ['--id' => $first->id])
            ->expectsOutputToContain('Found 1 media to regenerate.')
            ->assertSuccessful();

        Queue::assertPushed(RegenerateMediaImageCache::class, 1);
        Queue::assertPushed(RegenerateMediaImageCache::class, fn (RegenerateMediaImageCache $job): bool => $job->mediaId === $first->id);
    }

    protected function processedMedia(string $path = 'images/photo.jpg', int $width = 1200, int $height = 800): Media
    {
        Storage::disk('public')->put($path, $this->jpegBytes($width, $height));

        $media = Media::factory()->make([
            'file_path' => $path,
            'disk' => 'public',
        ]);
        $media->saveQuietly();

        app(MediaProcessor::class)->process($media);

        return $media;
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
