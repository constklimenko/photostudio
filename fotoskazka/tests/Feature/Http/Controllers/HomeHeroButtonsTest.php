<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Services\HomeContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_renders_published_category_marked_for_home(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'name' => 'Выпускные альбомы',
            'slug' => 'vypusknye-albomy',
            'is_published' => true,
            'show_on_home' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Выпускные альбомы')
            ->assertSee(route('services.show', $category->catalogPath()), false);
    }

    public function test_hero_hides_category_without_home_flag(): void
    {
        Category::factory()->create([
            'type' => 'service',
            'name' => 'Семейная фотосессия',
            'is_published' => true,
            'show_on_home' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Семейная фотосессия');
    }

    public function test_hero_hides_unpublished_category(): void
    {
        Category::factory()->create([
            'type' => 'service',
            'name' => 'Лавстори',
            'is_published' => false,
            'show_on_home' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Лавстори');
    }

    public function test_hero_hides_post_categories(): void
    {
        Category::factory()->create([
            'type' => 'post',
            'name' => 'Журнал',
            'is_published' => true,
            'show_on_home' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Журнал');
    }

    public function test_hero_renders_nested_category_with_full_path(): void
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'name' => 'Свадьбы',
            'slug' => 'svadby',
            'is_published' => true,
            'show_on_home' => false,
        ]);

        $child = Category::factory()->create([
            'type' => 'service',
            'parent_id' => $root->id,
            'name' => 'Свадьба в Уфе',
            'slug' => 'svadba-v-ufe',
            'is_published' => true,
            'show_on_home' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Свадьба в Уфе')
            ->assertSee(route('services.show', 'svadby/svadba-v-ufe'), false);

        $this->assertSame('svadby/svadba-v-ufe', $child->fresh()->catalogPath());
    }

    public function test_hero_renders_published_service_marked_for_home(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'slug' => 'prazdniki',
            'is_published' => true,
        ]);

        $service = Service::factory()->create([
            'title' => 'День рождения',
            'slug' => 'den-rozhdeniya',
            'category_id' => $category->id,
            'is_published' => true,
            'show_on_home' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('День рождения')
            ->assertSee(route('services.show', $service->catalogPath()), false);
    }

    public function test_hero_hides_service_without_home_flag(): void
    {
        Service::factory()->create([
            'title' => 'Предсъёмка',
            'is_published' => true,
            'show_on_home' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Предсъёмка');
    }

    public function test_hero_hides_unpublished_service(): void
    {
        Service::factory()->create([
            'title' => 'Корпоратив',
            'is_published' => false,
            'show_on_home' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Корпоратив');
    }

    public function test_hero_buttons_are_sorted_by_sort_order_then_type_then_title(): void
    {
        $serviceLast = Service::factory()->create([
            'title' => 'Ааа последняя услуга',
            'is_published' => true,
            'show_on_home' => true,
            'sort_order' => 30,
        ]);

        $categorySecond = Category::factory()->create([
            'type' => 'service',
            'name' => 'Вторая категория',
            'is_published' => true,
            'show_on_home' => true,
            'sort_order' => 20,
        ]);

        $serviceFirst = Service::factory()->create([
            'title' => 'Яя первая услуга',
            'is_published' => true,
            'show_on_home' => true,
            'sort_order' => 10,
        ]);

        $categoryFirst = Category::factory()->create([
            'type' => 'service',
            'name' => 'Ааа первая категория',
            'is_published' => true,
            'show_on_home' => true,
            'sort_order' => 10,
        ]);

        $labels = array_column(app(HomeContentService::class)->heroButtons(), 'label');

        $this->assertSame([
            'Ааа первая категория',
            'Яя первая услуга',
            'Вторая категория',
            'Ааа последняя услуга',
        ], $labels);
    }

    public function test_hero_buttons_keep_single_type_order_independent_from_other_type(): void
    {
        Service::factory()->create([
            'title' => 'Вторая услуга',
            'is_published' => true,
            'show_on_home' => true,
            'sort_order' => 5,
        ]);

        Service::factory()->create([
            'title' => 'Первая услуга',
            'is_published' => true,
            'show_on_home' => true,
            'sort_order' => 1,
        ]);

        $labels = array_column(app(HomeContentService::class)->heroButtons(), 'label');

        $this->assertSame(['Первая услуга', 'Вторая услуга'], $labels);
    }

    public function test_hero_does_not_render_inquiry_cta(): void
    {
        $response = $this->get('/');

        $hero = $this->heroSection($response->getContent());

        $this->assertNotSame('', $hero);
        $this->assertStringNotContainsString('Записаться на съёмку', $hero);
        $this->assertStringNotContainsString('inquiry.store', $hero);
        $this->assertStringNotContainsString('data-open-modal', $hero);
    }

    public function test_hero_renders_no_buttons_when_nothing_is_enabled(): void
    {
        $response = $this->get('/');

        $hero = $this->heroSection($response->getContent());

        $this->assertNotSame('', $hero);
        $this->assertStringNotContainsString('data-hero-button', $hero);
    }

    private function heroSection(string $html): string
    {
        preg_match('/<section[^>]*id="hero-block".*?<\/section>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
