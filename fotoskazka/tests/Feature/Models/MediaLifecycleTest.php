<?php

namespace Tests\Feature\Models;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
    }

    public function test_creating_media_fills_metadata_and_generates_thumbnail(): void
    {
        UploadedFile::fake()->image('photo.jpg', 1000, 500)->storeAs('images', 'photo.jpg', 'public');

        $media = Media::query()->create([
            'file_path' => 'images/photo.jpg',
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(1000, $media->width);
        $this->assertSame(500, $media->height);
        $this->assertNotNull($media->file_size);
        $this->assertNotNull($media->thumbnail_path);
        $this->assertSame('images/photo_thumb.webp', $media->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);
    }

    public function test_updating_media_does_not_break_existing_processing(): void
    {
        UploadedFile::fake()->image('photo.jpg', 400, 400)->storeAs('images', 'photo.jpg', 'public');

        $media = Media::query()->create([
            'file_path' => 'images/photo.jpg',
            'disk' => 'public',
        ]);
        $media->refresh();

        $thumbnailContent = Storage::disk('thumbnails')->get($media->thumbnail_path);

        $media->update(['title' => 'Updated title', 'alt_text' => 'Alt']);

        $media->refresh();

        $this->assertSame('Updated title', $media->title);
        $this->assertSame(400, $media->width);
        $this->assertSame('images/photo_thumb.webp', $media->thumbnail_path);
        $this->assertSame($thumbnailContent, Storage::disk('thumbnails')->get($media->thumbnail_path));
    }

    public function test_deleting_media_removes_record_but_keeps_files(): void
    {
        $path = UploadedFile::fake()->image('photo.jpg', 300, 300)->store('images', 'public');

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);
        $thumbnailPath = $media->thumbnail_path;

        $media->delete();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertExists($path);
        Storage::disk('thumbnails')->assertExists($thumbnailPath);
    }
}
