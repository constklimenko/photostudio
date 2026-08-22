<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
    }

    public function test_serves_original_through_proxy(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 80);
        $path = $file->store('images', 'public');

        $media = Media::factory()->create([
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);

        $response = $this->get(route('media.original', ['media' => $media]));

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'image/jpeg');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertSame(Storage::disk('public')->get($path), $content);
    }

    public function test_returns_404_for_missing_file(): void
    {
        $media = Media::factory()->create([
            'file_path' => 'images/nonexistent.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);

        $this->get(route('media.original', ['media' => $media]))
            ->assertNotFound();
    }

    public function test_sets_cache_header(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 80);
        $path = $file->store('images', 'public');

        $media = Media::factory()->create([
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);

        $this->get(route('media.original', ['media' => $media]))
            ->assertHeaderContains('Cache-Control', 'max-age=86400')
            ->assertHeaderContains('Cache-Control', 'public');
    }
}
