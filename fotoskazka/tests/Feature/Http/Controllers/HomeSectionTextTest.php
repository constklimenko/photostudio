<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeSectionTextTest extends TestCase
{
    use RefreshDatabase;

    private function createServicesCategory(): Category
    {
        return Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'name' => 'Выпускные альбомы',
        ]);
    }

    public function test_block_uses_page_title_when_home_title_empty(): void
    {
        $this->createServicesCategory();

        Page::factory()->create([
            'slug' => 'services',
            'title' => 'Услуги студии',
            'subtitle' => 'Подзаголовок тематической страницы',
            'home_title' => null,
            'home_subtitle' => null,
            'show_on_home' => true,
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Услуги студии')
            ->assertSee('Подзаголовок тематической страницы');
    }

    public function test_block_prefers_home_title_over_page_title(): void
    {
        $this->createServicesCategory();

        Page::factory()->create([
            'slug' => 'services',
            'title' => 'Заголовок страницы',
            'subtitle' => 'Подзаголовок страницы',
            'home_title' => 'Заголовок блока на главной',
            'home_subtitle' => 'Подзаголовок блока на главной',
            'show_on_home' => true,
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Заголовок блока на главной')
            ->assertSee('Подзаголовок блока на главной')
            ->assertDontSee('Заголовок страницы');
    }

    public function test_block_uses_default_text_when_page_missing(): void
    {
        $this->createServicesCategory();

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Наши услуги')
            ->assertSee('Выберите подходящий формат съёмки');
    }

    public function test_block_renders_home_content_description(): void
    {
        $this->createServicesCategory();

        Page::factory()->create([
            'slug' => 'services',
            'title' => 'Услуги',
            'home_title' => null,
            'home_subtitle' => null,
            'home_content' => '<p>Индивидуальные форматы съёмки под ваш праздник</p>',
            'show_on_home' => true,
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Индивидуальные форматы съёмки под ваш праздник');
    }

    public function test_block_description_falls_back_to_page_content(): void
    {
        $this->createServicesCategory();

        Page::factory()->create([
            'slug' => 'services',
            'title' => 'Услуги',
            'home_title' => null,
            'home_subtitle' => null,
            'home_content' => null,
            'content' => '<p>Описание тематической страницы</p>',
            'show_on_home' => true,
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Описание тематической страницы');
    }
}
