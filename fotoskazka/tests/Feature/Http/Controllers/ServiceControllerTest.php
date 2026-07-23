<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Service;
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
}
