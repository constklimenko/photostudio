<?php

namespace Tests\Feature\Actions;

use App\Actions\Media\RotateMedia;
use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RotateMediaTest extends TestCase
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

    public function test_rotates_ninety_degrees_clockwise(): void
    {
        $media = $this->createMediaWithFiles('public');

        $this->assertSame(600, $media->width);
        $this->assertSame(400, $media->height);

        $result = app(RotateMedia::class)->execute($media, 90);

        $this->assertTrue($result);

        $media->refresh();

        $this->assertSame(400, $media->width);
        $this->assertSame(600, $media->height);
        $this->assertDimensions($this->localDisk, (string) $media->file_path, 400, 600);
        $this->assertDimensions($this->thumbnailDisk, (string) $media->thumbnail_path, 267, 400);
        $this->assertImageCacheDimensions($media, 400, 600);
    }

    public function test_rotates_two_hundred_seventy_degrees_clockwise(): void
    {
        $media = $this->createMediaWithFiles('public');

        $result = app(RotateMedia::class)->execute($media, 270);

        $this->assertTrue($result);

        $media->refresh();

        $this->assertSame(400, $media->width);
        $this->assertSame(600, $media->height);
        $this->assertDimensions($this->localDisk, (string) $media->file_path, 400, 600);
    }

    public function test_rotates_hundred_eighty_degrees_keeps_dimensions(): void
    {
        $media = $this->createMediaWithFiles('public');
        $thumbnailBefore = $media->thumbnail_path;

        $result = app(RotateMedia::class)->execute($media, 180);

        $this->assertTrue($result);

        $media->refresh();

        $this->assertSame(600, $media->width);
        $this->assertSame(400, $media->height);
        $this->assertDimensions($this->localDisk, (string) $media->file_path, 600, 400);
        $this->assertDimensions($this->thumbnailDisk, (string) $media->thumbnail_path, 400, 267);
        $this->assertSame($thumbnailBefore, $media->thumbnail_path);
    }

    public function test_rotates_original_on_remote_disk(): void
    {
        $media = $this->createMediaWithFiles('yandex_disk');

        $result = app(RotateMedia::class)->execute($media, 90);

        $this->assertTrue($result);

        $media->refresh();

        $this->assertSame(400, $media->width);
        $this->assertSame(600, $media->height);
        $this->assertDimensions($this->remoteDisk, (string) $media->file_path, 400, 600);
    }

    public function test_rejects_angle_not_multiple_of_ninety(): void
    {
        $media = $this->createMediaWithFiles('public');

        $result = app(RotateMedia::class)->execute($media, 45);

        $this->assertFalse($result);

        $media->refresh();

        $this->assertSame(600, $media->width);
        $this->assertSame(400, $media->height);
        $this->assertDimensions($this->localDisk, (string) $media->file_path, 600, 400);
    }

    public function test_returns_false_for_zero_angle(): void
    {
        $media = $this->createMediaWithFiles('public');
        $path = (string) $media->file_path;

        $result = app(RotateMedia::class)->execute($media, 0);

        $this->assertFalse($result);
        $this->assertDimensions($this->localDisk, $path, 600, 400);
    }

    public function test_returns_false_when_original_missing(): void
    {
        Log::spy();

        $media = $this->createMediaWithFiles('public');
        $this->localDisk->delete((string) $media->file_path);

        $result = app(RotateMedia::class)->execute($media, 90);

        $this->assertFalse($result);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_returns_false_for_unsupported_format(): void
    {
        Log::spy();

        $media = $this->createGifMedia();

        $result = app(RotateMedia::class)->execute($media, 90);

        $this->assertFalse($result);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_write_failure_keeps_original_and_record(): void
    {
        Log::spy();

        $media = $this->createMediaWithFiles('public');

        $failingDisk = Mockery::mock(Filesystem::class);
        $failingDisk->shouldReceive('exists')->andReturnTrue();
        $failingDisk->shouldReceive('readStream')->andReturn(fopen(
            $this->localDisk->path((string) $media->file_path),
            'rb',
        ));
        $failingDisk->shouldReceive('put')->andReturn(false);

        Storage::shouldReceive('disk')->andReturnUsing(fn (string $name): Filesystem => match ($name) {
            'public' => $failingDisk,
            'thumbnails' => $this->thumbnailDisk,
            'image_cache' => $this->cacheDisk,
            default => $this->remoteDisk,
        });

        $result = app(RotateMedia::class)->execute($media, 90);

        $this->assertFalse($result);

        $media->refresh();

        $this->assertSame(600, $media->width);
        $this->assertSame(400, $media->height);
        $this->assertDimensions($this->localDisk, (string) $media->file_path, 600, 400);
        Log::shouldHaveReceived('error')->withArgs(fn (string $message): bool => $message === 'Unable to write rotated original to storage.')->once();
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

        return $media;
    }

    protected function createGifMedia(): Media
    {
        $image = imagecreatetruecolor(200, 200);

        ob_start();
        imagegif($image);
        $content = ob_get_clean();
        imagedestroy($image);

        $this->localDisk->put('images/animated.gif', $content);

        $media = Media::query()->create([
            'file_path' => 'images/animated.gif',
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        return $media->refresh();
    }

    protected function assertDimensions(Filesystem $disk, string $path, int $width, int $height): void
    {
        $this->assertTrue($disk->exists($path), "File not found on disk: {$path}");

        $temp = tempnam(sys_get_temp_dir(), 'dims-');

        try {
            $stream = $disk->readStream($path);
            file_put_contents($temp, stream_get_contents($stream));
            fclose($stream);

            $info = @getimagesize($temp);

            $this->assertNotFalse($info, 'File is not a readable image.');
            $this->assertSame([$width, $height], [$info[0], $info[1]]);
        } finally {
            @unlink($temp);
        }
    }

    protected function assertImageCacheDimensions(Media $media, int $width, int $height): void
    {
        $service = app(ImageCacheService::class);

        foreach (array_keys($service->tiers()) as $tier) {
            $this->assertDimensions($this->cacheDisk, $service->relativePath($media, (string) $tier), $width, $height);
        }
    }
}
