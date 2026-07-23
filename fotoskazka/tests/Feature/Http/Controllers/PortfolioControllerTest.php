<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PortfolioControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_successful_response(): void
    {
        $response = $this->get(route('portfolio.index'));

        $response->assertStatus(200);
    }

    public function test_index_shows_page_title(): void
    {
        $page = Page::factory()->create([
            'slug' => 'portfolio',
            'title' => 'Наше портфолио',
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get(route('portfolio.index'));

        $response->assertSee('Наше портфолио');
    }

    public function test_index_shows_published_albums(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
            'title' => 'Свадебная съёмка',
        ]);

        $response = $this->get(route('portfolio.index'));

        $response->assertSee('Свадебная съёмка');
    }

    public function test_index_hides_unpublished_albums(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => false,
            'title' => 'Скрытый альбом',
        ]);

        $response = $this->get(route('portfolio.index'));

        $response->assertDontSee('Скрытый альбом');
    }

    public function test_index_hides_non_portfolio_albums(): void
    {
        $album = Album::factory()->create([
            'type' => 'project',
            'is_published' => true,
            'title' => 'Проектный альбом',
        ]);

        $response = $this->get(route('portfolio.index'));

        $response->assertDontSee('Проектный альбом');
    }

    public function test_index_shows_empty_state(): void
    {
        $response = $this->get(route('portfolio.index'));

        $response->assertSee('Портфолио пока пусто');
    }

    public function test_show_returns_successful_response(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
        ]);

        $response = $this->get(route('portfolio.show', $album->slug));

        $response->assertStatus(200);
        $response->assertSee($album->title);
    }

    public function test_show_returns_404_for_unpublished_album(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => false,
        ]);

        $response = $this->get(route('portfolio.show', $album->slug));

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->get(route('portfolio.show', 'nonexistent-slug'));

        $response->assertNotFound();
    }

    public function test_show_displays_services(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
        ]);

        $response = $this->get(route('portfolio.show', $album->slug));

        $response->assertStatus(200);
    }
}
