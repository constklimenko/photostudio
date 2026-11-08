<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Media;
use App\Models\Page;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeReviewsTest extends TestCase
{
    use RefreshDatabase;

    private function makePhoto(Album $album, int $sortOrder = 0, ?string $caption = null): Photo
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'reviews/'.uniqid().'.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        return Photo::factory()->create([
            'album_id' => $album->getKey(),
            'media_id' => $media->getKey(),
            'sort_order' => $sortOrder,
            'caption' => $caption,
        ]);
    }

    private function bindToHome(Album $album): void
    {
        $home = Page::query()->where('slug', 'home')->first()
            ?? Page::factory()->create(['slug' => 'home', 'is_published' => true]);

        $home->albums()->attach($album->getKey());

        Cache::flush();
    }

    public function test_home_hides_reviews_without_albums(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('data-home-block="reviews"', false);
    }

    public function test_home_shows_reviews_from_album_bound_to_home(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $photo = $this->makePhoto($album, 1, 'Спасибо за съёмку!');
        $this->bindToHome($album);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-home-block="reviews"', false);
        $response->assertSee('Отзывы');
        $response->assertSee($photo->media->getDisplayUrl(), false);
    }

    public function test_home_hides_reviews_for_unpublished_album(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => false,
        ]);

        $this->makePhoto($album);
        $this->bindToHome($album);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('data-home-block="reviews"', false);
    }

    public function test_home_hides_reviews_for_album_bound_to_other_page(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
        ]);

        $photo = $this->makePhoto($album);

        $other = Page::factory()->create(['slug' => 'services', 'is_published' => true]);
        $other->albums()->attach($album->getKey());
        Cache::flush();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('data-home-block="reviews"', false);
        $response->assertDontSee($photo->media->getDisplayUrl(), false);
    }

    public function test_home_reviews_respect_album_and_photo_order(): void
    {
        $first = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
            'sort_order' => 1,
        ]);
        $second = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        $photoSecond = $this->makePhoto($second, 1);
        $photoFirstB = $this->makePhoto($first, 2);
        $photoFirstA = $this->makePhoto($first, 1);

        $this->bindToHome($first);
        Page::query()->where('slug', 'home')->first()->albums()->attach($second->getKey());
        Cache::flush();

        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();
        $posA = strpos($html, $photoFirstA->media->getDisplayUrl());
        $posB = strpos($html, $photoFirstB->media->getDisplayUrl());
        $posSecond = strpos($html, $photoSecond->media->getDisplayUrl());

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posSecond);
        $this->assertLessThan($posB, $posA);
        $this->assertLessThan($posSecond, $posB);
    }
}
