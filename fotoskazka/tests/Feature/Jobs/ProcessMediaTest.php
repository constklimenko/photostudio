<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Services\ImageCacheService;
use App\Services\MediaProcessor;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProcessMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_creating_media_dispatches_process_media_job(): void
    {
        Queue::fake();

        $path = $this->storeJpeg(800, 600);

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        Queue::assertPushed(ProcessMedia::class, function (ProcessMedia $job) use ($media): bool {
            return $job->mediaId === $media->id;
        });
        Queue::assertPushed(ProcessMedia::class, 1);
    }

    public function test_mass_upload_dispatches_one_job_per_media(): void
    {
        Queue::fake();

        $ids = [];

        foreach (range(1, 5) as $index) {
            $ids[] = Media::query()->create([
                'file_path' => $this->storeJpeg(400, 400, "images/photo-{$index}.jpg"),
                'disk' => 'public',
                'collection' => 'gallery',
            ])->id;
        }

        Queue::assertPushed(ProcessMedia::class, 5);
        Queue::assertPushed(ProcessMedia::class, fn (ProcessMedia $job): bool => in_array($job->mediaId, $ids, true));
    }

    public function test_job_is_not_dispatched_until_transaction_commits(): void
    {
        config(['queue.default' => 'database']);

        DB::beginTransaction();

        $media = Media::query()->create([
            'file_path' => $this->storeJpeg(600, 600),
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        $this->assertDatabaseCount('jobs', 0);

        DB::commit();

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['id' => 1]);
        $this->assertStringContainsString((string) $media->id, DB::table('jobs')->value('payload'));
    }

    public function test_job_is_discarded_on_transaction_rollback(): void
    {
        config(['queue.default' => 'database']);

        DB::beginTransaction();

        Media::query()->create([
            'file_path' => $this->storeJpeg(600, 600),
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        DB::rollBack();

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_handle_processes_media_successfully(): void
    {
        $path = $this->storeJpeg(1000, 500);

        $media = $this->makeUnprocessedMedia($path);

        $this->runJob($media->id);

        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(1000, $media->width);
        $this->assertSame(500, $media->height);
        $this->assertNotNull($media->file_size);
        $this->assertSame('images/test_thumb.webp', $media->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);

        $cacheDisk = Storage::disk('image_cache');
        $service = new ImageCacheService;

        foreach ([ImageCacheService::TIER_DISPLAY => 800, ImageCacheService::TIER_LIGHTBOX => 1600] as $tier => $maxSide) {
            $variantPath = $service->relativePath($media, $tier);

            $cacheDisk->assertExists($variantPath);

            [$width, $height] = getimagesize($cacheDisk->path($variantPath));

            $this->assertLessThanOrEqual($maxSide, max($width, $height));
        }
    }

    public function test_repeated_execution_is_idempotent(): void
    {
        $media = $this->makeUnprocessedMedia($this->storeJpeg(800, 600));

        $this->runJob($media->id);
        $media->refresh();

        $metadataBefore = $media->only(['mime_type', 'width', 'height', 'file_size', 'thumbnail_path']);
        $updatedAtBefore = $media->updated_at->toString();
        $thumbnailBefore = Storage::disk('thumbnails')->get($media->thumbnail_path);
        $displayPath = (new ImageCacheService)->relativePath($media, ImageCacheService::TIER_DISPLAY);
        $displayBefore = Storage::disk('image_cache')->get($displayPath);

        sleep(1);
        $this->runJob($media->id);

        $media->refresh();
        $thumbDisk = Storage::disk('thumbnails');
        $cacheDisk = Storage::disk('image_cache');

        $this->assertSame($metadataBefore, $media->only(['mime_type', 'width', 'height', 'file_size', 'thumbnail_path']));
        $this->assertSame($updatedAtBefore, $media->updated_at->toString());
        $this->assertSame($thumbnailBefore, $thumbDisk->get($media->thumbnail_path));
        $this->assertSame($displayBefore, $cacheDisk->get($displayPath));
        $this->assertCount(1, $thumbDisk->allFiles());
        $this->assertCount(2, $cacheDisk->allFiles());
        $this->assertSame(1, Media::query()->count());
    }

    public function test_missing_media_is_ignored_without_failure(): void
    {
        Log::shouldReceive('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'ProcessMedia: media record not found.'
                && $context['media_id'] === 99999,
        );

        $this->runJob(99999);

        $this->assertDatabaseCount('media', 0);
    }

    public function test_missing_original_completes_job_without_failure(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $media = $this->makeUnprocessedMedia('images/never-uploaded.jpg');

        $this->runJob($media->id);

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->assertCount(0, Storage::disk('thumbnails')->allFiles());
    }

    public function test_corrupted_image_keeps_record_and_does_not_fail_job(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $full = $this->jpegBytes(400, 300);
        Storage::disk('public')->put('images/broken.jpg', substr($full, 0, 64));

        $media = $this->makeUnprocessedMedia('images/broken.jpg');

        $this->runJob($media->id);

        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertNull($media->width);
        $this->assertNull($media->thumbnail_path);
    }

    public function test_temporary_storage_error_is_rethrown_for_retry(): void
    {
        Log::shouldReceive('error')->once();

        $mock = Mockery::mock(Filesystem::class);
        $mock->shouldReceive('exists')
            ->andThrow(new RuntimeException('storage temporarily unavailable'));

        Storage::shouldReceive('disk')->andReturn($mock);

        $media = $this->makeUnprocessedMedia('images/any.jpg');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('storage temporarily unavailable');

        $this->runJob($media->id);
    }

    public function test_retry_configuration_is_sane_for_photo_processing(): void
    {
        $job = new ProcessMedia(1);

        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertTrue($job->afterCommit);
        $this->assertSame([30, 120], $job->backoff());
    }

    protected function runJob(int $mediaId): void
    {
        (new ProcessMedia($mediaId))->handle(app(MediaProcessor::class));
    }

    protected function makeUnprocessedMedia(string $filePath): Media
    {
        $media = Media::factory()->make([
            'file_path' => $filePath,
            'disk' => 'public',
            'collection' => 'gallery',
            'mime_type' => null,
            'width' => null,
            'height' => null,
            'file_size' => null,
            'thumbnail_path' => null,
        ]);

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
