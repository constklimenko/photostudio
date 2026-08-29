<?php

namespace Tests\Feature\Console;

use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('yandex_disk');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_shows_ok_for_complete_media(): void
    {
        $this->createCompleteMedia();

        $this->artisan('media:check')
            ->expectsOutputToContain('Checked: 1')
            ->expectsOutputToContain('OK: 1')
            ->assertSuccessful();
    }

    public function test_reports_missing_original(): void
    {
        Media::query()->create([
            'file_path' => 'images/missing.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);

        $this->artisan('media:check')
            ->expectsOutputToContain('Checked: 1')
            ->expectsOutputToContain('Missing original: 1')
            ->assertExitCode(1);
    }

    public function test_reports_missing_thumbnail(): void
    {
        $media = $this->createMediaWithOriginal('images/photo.jpg');
        $media->update([
            'mime_type' => 'image/jpeg',
            'thumbnail_path' => null,
        ]);

        $this->artisan('media:check')
            ->expectsOutputToContain('Checked: 1')
            ->expectsOutputToContain('Missing thumbnail: 1')
            ->assertSuccessful();
    }

    public function test_reports_metadata_mismatch(): void
    {
        $media = $this->createCompleteMedia();
        $media->update(['file_size' => 0]);

        $this->artisan('media:check')
            ->expectsOutputToContain('Checked: 1')
            ->expectsOutputToContain('Metadata mismatch: 1')
            ->assertSuccessful();
    }

    public function test_reports_orphan_yandex_files(): void
    {
        Storage::disk('yandex_disk')->put('images/orphan.jpg', 'content');

        $this->artisan('media:check')
            ->expectsOutputToContain('Potential orphan Yandex files: 1')
            ->assertSuccessful();
    }

    public function test_limit_reduces_checked_count(): void
    {
        $this->createMediaWithOriginal('images/one.jpg');
        $this->createMediaWithOriginal('images/two.jpg');
        $this->createMediaWithOriginal('images/three.jpg');

        $this->artisan('media:check', ['--limit' => 1])
            ->expectsOutputToContain('OK: 1')
            ->expectsOutputToContain('Skipped (limit): 2')
            ->assertSuccessful();
    }

    public function test_media_id_checks_only_that_record(): void
    {
        $this->createMediaWithOriginal('images/one.jpg');
        $target = $this->createMediaWithOriginal('images/two.jpg');
        $target->update(['mime_type' => 'image/jpeg', 'thumbnail_path' => null]);

        $this->artisan('media:check', ['--media-id' => $target->id])
            ->expectsOutputToContain('Checked: 1')
            ->expectsOutputToContain('Missing thumbnail: 1')
            ->assertSuccessful();
    }

    public function test_nonexistent_media_id_fails(): void
    {
        $this->artisan('media:check', ['--media-id' => 99999])
            ->expectsOutputToContain('не найдена')
            ->assertExitCode(1);
    }

    public function test_fix_thumbnails_regenerates_missing(): void
    {
        $media = $this->createMediaWithRealImage('images/photo.jpg');
        $media->update(['thumbnail_path' => null]);

        $this->artisan('media:check', ['--fix-thumbnails' => true])
            ->expectsOutputToContain('Восстановлено thumbnails: 1')
            ->assertSuccessful();

        $media->refresh();
        $this->assertNotEmpty($media->thumbnail_path);
    }

    public function test_fix_thumbnails_reports_zero_when_nothing_to_fix(): void
    {
        $this->createCompleteMedia();

        $this->artisan('media:check', ['--fix-thumbnails' => true])
            ->expectsOutputToContain('Восстановлено thumbnails: 0')
            ->assertSuccessful();
    }

    public function test_mixed_media_produces_summary(): void
    {
        $this->createCompleteMedia();
        Media::query()->create([
            'file_path' => 'images/missing.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);
        $partial = $this->createMediaWithOriginal('images/partial.jpg');
        $partial->update(['mime_type' => 'image/jpeg', 'thumbnail_path' => null]);

        $this->artisan('media:check')
            ->expectsOutputToContain('Checked: 3')
            ->expectsOutputToContain('OK: 1')
            ->expectsOutputToContain('Missing original: 1')
            ->expectsOutputToContain('Missing thumbnail: 1')
            ->assertExitCode(1);
    }

    protected function createCompleteMedia(): Media
    {
        $media = $this->createMediaWithOriginal('images/complete.jpg');

        $thumbPath = 'images/complete_thumb.webp';
        Storage::disk('thumbnails')->put($thumbPath, 'thumb-content');

        $cache = app(ImageCacheService::class);
        Storage::disk('image_cache')->put($cache->relativePath($media, 'display'), 'cache-display');
        Storage::disk('image_cache')->put($cache->relativePath($media, 'lightbox'), 'cache-lightbox');

        $media->update([
            'thumbnail_path' => $thumbPath,
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'file_size' => 102400,
        ]);

        return $media->fresh();
    }

    protected function createMediaWithOriginal(string $path): Media
    {
        Storage::disk('public')->put($path, str_repeat('x', 102400));

        return Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);
    }

    protected function createMediaWithRealImage(string $path): Media
    {
        UploadedFile::fake()->image(basename($path), 600, 400)->storeAs(
            dirname($path),
            basename($path),
            'public',
        );

        $media = Media::query()->create([
            'file_path' => $path,
            'disk' => 'public',
        ]);

        $media->refresh();

        return $media;
    }
}
