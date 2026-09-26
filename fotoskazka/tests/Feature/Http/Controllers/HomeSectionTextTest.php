<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Page;
use App\Services\HomeContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeSectionTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_uses_page_title_when_home_title_empty(): void
    {
        $this->enablePortfolioSection([
            'title' => 'Портфолио студии',
            'subtitle' => 'Подборка лучших кадров',
            'home_title' => null,
            'home_subtitle' => null,
        ]);

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный кадр',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Портфолио студии')
            ->assertSee('Подборка лучших кадров')
            ->assertSee('Избранный кадр');
    }

    public function test_block_prefers_home_title_over_page_title(): void
    {
        $this->enablePortfolioSection([
            'title' => 'Заголовок страницы',
            'subtitle' => 'Подзаголовок страницы',
            'home_title' => 'Заголовок блока на главной',
            'home_subtitle' => 'Подзаголовок блока на главной',
        ]);

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный кадр',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Заголовок блока на главной')
            ->assertSee('Подзаголовок блока на главной')
            ->assertDontSee('Заголовок страницы');
    }

    public function test_block_uses_default_text_when_page_missing(): void
    {
        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный кадр',
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Избранный кадр')
            ->assertDontSee('data-home-block="featured-works"', false);
    }

    public function test_block_renders_home_content_description(): void
    {
        $this->enablePortfolioSection([
            'title' => 'Портфолио',
            'home_content' => '<p>Лучшие кадры нашей студии</p>',
        ]);

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный кадр',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Лучшие кадры нашей студии');
    }

    public function test_block_description_falls_back_to_page_content(): void
    {
        $this->enablePortfolioSection([
            'title' => 'Портфолио',
            'home_content' => null,
            'content' => '<p>Описание тематической страницы</p>',
        ]);

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный кадр',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Описание тематической страницы');
    }

    public function test_block_text_strips_html_from_page_content(): void
    {
        $this->enablePortfolioSection([
            'title' => 'Портфолио',
            'home_content' => null,
            'content' => '<p>Текст <strong>описания</strong></p>',
        ]);

        $text = app(HomeContentService::class)->blockText('portfolio', 'Избранные работы');

        $this->assertSame('Текст описания', $text['content']);
    }

    public function test_block_text_returns_defaults_for_unknown_page(): void
    {
        Cache::flush();

        $text = app(HomeContentService::class)->blockText(
            'services',
            'Наши услуги',
            'Выберите подходящий формат съёмки',
        );

        $this->assertSame([
            'title' => 'Наши услуги',
            'subtitle' => 'Выберите подходящий формат съёмки',
            'content' => null,
        ], $text);
    }

    public function test_block_text_ignores_pages_hidden_on_home(): void
    {
        Page::factory()->create([
            'slug' => 'services',
            'title' => 'Услуги студии',
            'show_on_home' => true,
            'is_published' => false,
        ]);

        Cache::flush();

        $text = app(HomeContentService::class)->blockText('services', 'Наши услуги');

        $this->assertSame('Наши услуги', $text['title']);
    }

    private function enablePortfolioSection(array $attributes = []): Page
    {
        $page = Page::factory()->create(array_merge([
            'slug' => 'portfolio',
            'title' => 'Портфолио',
            'is_published' => true,
            'show_on_home' => true,
        ], $attributes));

        Cache::flush();

        return $page;
    }
}
