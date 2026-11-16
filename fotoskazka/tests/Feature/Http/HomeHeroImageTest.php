<?php

namespace Tests\Feature\Http;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_serves_display_image_and_defers_original(): void
    {
        $media = Media::factory()->create([
            'mime_type' => 'image/jpeg',
        ]);

        $album = Album::factory()->create([
            'type' => 'homepage',
            'is_published' => true,
            'slug' => 'hero-album',
        ]);

        Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
            'sort_order' => 0,
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('src="'.route('media.display', ['media' => $media->id, 'v' => 'webp']).'"', false)
            ->assertSee('data-original="'.route('media.original', ['media' => $media->id]).'"', false)
            ->assertSee('fetchpriority="high"', false);
    }

    public function test_hero_falls_back_to_original_when_display_is_unavailable(): void
    {
        $media = Media::factory()->create([
            'mime_type' => 'application/octet-stream',
        ]);

        $album = Album::factory()->create([
            'type' => 'homepage',
            'is_published' => true,
            'slug' => 'hero-album-gif',
        ]);

        Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('src="'.route('media.original', ['media' => $media->id]).'"', false)
            ->assertDontSee('data-original=', false);
    }
}
