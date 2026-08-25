<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Category;
use App\Models\Page;
use App\Models\Service;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_successful_response(): void
    {
        $response = $this->get(route('services.index'));

        $response->assertStatus(200);
    }

    public function test_index_shows_page_title(): void
    {
        Page::factory()->create([
            'slug' => 'services',
            'title' => 'Наши услуги',
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get(route('services.index'));

        $response->assertSee('Наши услуги');
    }

    public function test_index_shows_published_services_with_category(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'name' => 'Выпускные альбомы',
        ]);

        $service = Service::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
            'title' => 'Выпускной в детском саду',
        ]);

        $response = $this->get(route('services.index'));

        $response->assertSee('Выпускные альбомы');
        $response->assertSee('Выпускной в детском саду');
    }

    public function test_index_shows_services_without_category(): void
    {
        $service = Service::factory()->create([
            'category_id' => null,
            'is_published' => true,
            'title' => 'Индивидуальная съёмка',
        ]);

        $response = $this->get(route('services.index'));

        $response->assertSee('Индивидуальная съёмка');
    }

    public function test_index_hides_unpublished_services(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'name' => 'Выпускные альбомы',
        ]);

        $service = Service::factory()->create([
            'category_id' => $category->id,
            'is_published' => false,
            'title' => 'Скрытая услуга',
        ]);

        $response = $this->get(route('services.index'));

        $response->assertDontSee('Скрытая услуга');
    }

    public function test_index_shows_price(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'title' => 'Семейная съёмка',
            'price_from' => 5000,
        ]);

        $response = $this->get(route('services.index'));

        $response->assertSee('5 000');
    }

    public function test_show_returns_successful_response(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'title' => 'Свадебная съёмка',
        ]);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertStatus(200);
        $response->assertSee('Свадебная съёмка');
    }

    public function test_show_returns_404_for_unpublished_service(): void
    {
        $service = Service::factory()->create([
            'is_published' => false,
        ]);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->get(route('services.show', 'nonexistent-service'));

        $response->assertNotFound();
    }

    public function test_show_displays_other_services(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'title' => 'Основная услуга',
        ]);

        $otherService = Service::factory()->create([
            'is_published' => true,
            'title' => 'Другая услуга',
        ]);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertSee('Другая услуга');
    }

    public function test_show_has_inquiry_form(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
        ]);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertSee('Оставить заявку');
    }

    public function test_show_displays_attached_videos(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'title' => 'Свадебная съёмка',
        ]);
        $video = Video::factory()->create([
            'type' => 'horizontal',
            'title' => 'Свадебный фильм',
        ]);
        $service->videos()->attach($video);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertSee('Свадебный фильм');
        $response->assertSee($video->embed_url);
    }

    public function test_show_displays_vertical_attached_videos(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
        ]);
        $video = Video::factory()->create([
            'type' => 'vertical',
            'title' => 'Reels ролик',
        ]);
        $service->videos()->attach($video);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertSee('Reels ролик');
        $response->assertSee($video->embed_url);
    }

    public function test_show_displays_custom_examples_title(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'examples_title' => 'Наши работы',
        ]);
        $album = Album::factory()->create(['is_published' => true]);
        $service->albums()->attach($album);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertSee('Наши работы');
        $response->assertDontSee('Примеры работ');
    }

    public function test_show_displays_default_examples_title_when_null(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'examples_title' => null,
        ]);
        $album = Album::factory()->create(['is_published' => true]);
        $service->albums()->attach($album);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertSee('Примеры работ');
    }

    public function test_show_does_not_render_examples_section_without_albums(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'examples_title' => 'Наши работы',
        ]);

        $response = $this->get(route('services.show', $service->slug));

        $response->assertDontSee('Наши работы');
        $response->assertDontSee('Примеры работ');
    }
}
