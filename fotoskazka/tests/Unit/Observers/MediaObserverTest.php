<?php

namespace Tests\Unit\Observers;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaObserverTest extends TestCase
{
    use RefreshDatabase;

    protected string $testImage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_fills_metadata_for_image(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $path = $file->store('images', 'public');

        $media = Media::factory()->create([
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => null,
            'width' => null,
            'height' => null,
            'file_size' => null,
        ]);

        $media->refresh();

        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertEquals(1920, $media->width);
        $this->assertEquals(1080, $media->height);
        $this->assertNotNull($media->file_size);
        $this->assertIsInt($media->file_size);
    }

    public function test_generates_webp_thumbnail_for_image(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $path = $file->store('images', 'public');

        $media = Media::factory()->create([
            'file_path' => $path,
            'disk' => 'public',
            'thumbnail_path' => null,
        ]);

        $media->refresh();

        $this->assertNotNull($media->thumbnail_path);
        $this->assertStringEndsWith('.webp', $media->thumbnail_path);
        Storage::disk('public')->assertExists($media->thumbnail_path);
    }

    public function test_thumbnail_is_max_400px_for_landscape(): void
    {
        $file = UploadedFile::fake()->image('landscape.jpg', 800, 400);
        $path = $file->store('images', 'public');

        $media = Media::factory()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);

        $media->refresh();

        $thumbPath = Storage::disk('public')->path($media->thumbnail_path);
        [$width, $height] = getimagesize($thumbPath);

        $this->assertLessThanOrEqual(400, $width);
        $this->assertLessThanOrEqual(400, $height);
    }

    public function test_thumbnail_is_max_400px_for_portrait(): void
    {
        $file = UploadedFile::fake()->image('portrait.jpg', 400, 800);
        $path = $file->store('images', 'public');

        $media = Media::factory()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);

        $media->refresh();

        $thumbPath = Storage::disk('public')->path($media->thumbnail_path);
        [$width, $height] = getimagesize($thumbPath);

        $this->assertLessThanOrEqual(400, $width);
        $this->assertLessThanOrEqual(400, $height);
    }

    public function test_does_not_generate_thumbnail_for_non_image(): void
    {
        Storage::disk('public')->put('documents/test.txt', 'plain text');

        $media = Media::factory()->create([
            'file_path' => 'documents/test.txt',
            'disk' => 'public',
            'thumbnail_path' => null,
        ]);

        $media->refresh();

        $this->assertNull($media->thumbnail_path);
    }

    public function test_does_not_crash_for_missing_file(): void
    {
        $media = Media::factory()->create([
            'file_path' => 'images/nonexistent.jpg',
            'disk' => 'public',
            'mime_type' => null,
            'width' => null,
            'height' => null,
            'file_size' => null,
            'thumbnail_path' => null,
        ]);

        $media->refresh();

        $this->assertNull($media->mime_type);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
        $this->assertNull($media->file_size);
        $this->assertNull($media->thumbnail_path);
    }
}
