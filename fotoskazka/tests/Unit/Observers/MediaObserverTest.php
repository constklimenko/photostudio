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

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
    }

    public function test_creating_defaults_disk_to_configured_media_disk(): void
    {
        Storage::fake('uploads');
        config(['filesystems.default_media_disk' => 'uploads']);

        $path = UploadedFile::fake()->image('photo.jpg')->store('images', 'uploads');

        $media = Media::query()->create([
            'file_path' => $path,
        ]);
        $media->refresh();

        $this->assertSame('uploads', $media->disk);
        $this->assertSame('image/jpeg', $media->mime_type);
    }

    public function test_created_event_triggers_processing(): void
    {
        $path = UploadedFile::fake()->image('photo.jpg', 640, 480)->store('images', 'public');

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);
        $media->refresh();

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(640, $media->width);
        $this->assertSame(480, $media->height);
        $this->assertNotNull($media->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);
    }

    public function test_update_does_not_reprocess_media(): void
    {
        $path = UploadedFile::fake()->image('photo.jpg', 300, 300)->store('images', 'public');

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);
        $media->refresh();
        $thumbnailPath = $media->thumbnail_path;

        Storage::disk('public')->delete($path);
        Storage::disk('thumbnails')->delete($thumbnailPath);

        $media->update(['title' => 'Renamed']);

        $media->refresh();

        $this->assertFalse(Storage::disk('thumbnails')->exists($thumbnailPath));
        $this->assertCount(0, Storage::disk('thumbnails')->allFiles());
    }
}
