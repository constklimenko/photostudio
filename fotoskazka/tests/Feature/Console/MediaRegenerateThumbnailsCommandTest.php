<?php

namespace Tests\Feature\Console;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaRegenerateThumbnailsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
    }

    protected function createProcessedMedia(string $path = 'images/photo.jpg', int $width = 800, int $height = 600): Media
    {
        UploadedFile::fake()->image('photo.jpg', $width, $height)->storeAs('images', basename($path), 'public');

        return Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);
    }

    public function test_regenerates_missing_thumbnail_file(): void
    {
        $media = $this->createProcessedMedia();
        $media->refresh();

        Storage::disk('thumbnails')->delete($media->thumbnail_path);

        $this->artisan('media:regenerate-thumbnails')->expectsOutputToContain('Done. Success: 1');

        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $media = $this->createProcessedMedia();
        $media->refresh();

        Storage::disk('thumbnails')->delete($media->thumbnail_path);
        $storedPath = $media->thumbnail_path;

        $this->artisan('media:regenerate-thumbnails', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        Storage::disk('thumbnails')->assertMissing($storedPath);
    }

    public function test_force_rewrites_broken_thumbnail_path_to_deterministic_one(): void
    {
        $media = $this->createProcessedMedia();
        $media->refresh();

        $media->forceFill(['thumbnail_path' => 'legacy/legacy_thumb.webp'])->saveQuietly();

        $this->artisan('media:regenerate-thumbnails', ['--force' => true])->assertSuccessful();

        $media->refresh();

        $this->assertSame('images/photo_thumb.webp', $media->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($media->thumbnail_path);
    }

    public function test_skips_complete_media_without_force(): void
    {
        $this->createProcessedMedia();

        $this->artisan('media:regenerate-thumbnails')
            ->expectsOutputToContain('No media found matching criteria.')
            ->assertSuccessful();

        $this->assertCount(0, Media::whereNull('thumbnail_path')->get());
    }
}
