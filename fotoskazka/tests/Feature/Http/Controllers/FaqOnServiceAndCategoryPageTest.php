<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\FaqItem;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqOnServiceAndCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(array $attributes = []): Category
    {
        return Category::factory()->create(array_merge([
            'type' => 'service',
            'is_published' => true,
            'parent_id' => null,
        ], $attributes));
    }

    private function makeService(Category $category, array $attributes = []): Service
    {
        return Service::factory()->create(array_merge([
            'category_id' => $category->id,
            'is_published' => true,
        ], $attributes));
    }

    private function makeFaq(array $attributes = []): FaqItem
    {
        return FaqItem::factory()->create(array_merge([
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    public function test_service_page_shows_faq_bound_to_service(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($category, ['title' => 'Свадебная съёмка']);
        $faq = $this->makeFaq(['question' => 'Сколько стоит съёмка?', 'answer' => 'От 20 000 рублей']);
        $faq->services()->attach($service);

        $response = $this->get(route('services.show', $service->catalogPath()));

        $response->assertOk();
        $response->assertSee('Часто задаваемые вопросы');
        $response->assertSee('Сколько стоит съёмка?');
        $response->assertSee('От 20 000 рублей');
    }

    public function test_service_page_hides_inactive_faq(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($category);
        $faq = $this->makeFaq(['question' => 'Скрытый вопрос', 'is_active' => false]);
        $faq->services()->attach($service);

        $response = $this->get(route('services.show', $service->catalogPath()));

        $response->assertOk();
        $response->assertDontSee('Скрытый вопрос');
        $response->assertDontSee('Часто задаваемые вопросы');
    }

    public function test_service_page_shows_only_faq_bound_to_that_service(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($category, ['title' => 'Классика']);
        $otherService = $this->makeService($category, ['title' => 'Премиум']);

        $faq = $this->makeFaq(['question' => 'Вопрос этой услуги']);
        $faq->services()->attach($service);

        $otherFaq = $this->makeFaq(['question' => 'Вопрос другой услуги']);
        $otherFaq->services()->attach($otherService);

        $response = $this->get(route('services.show', $service->catalogPath()));

        $response->assertSee('Вопрос этой услуги');
        $response->assertDontSee('Вопрос другой услуги');
    }

    public function test_service_page_does_not_show_category_faq(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($category);

        $categoryFaq = $this->makeFaq(['question' => 'Вопрос категории']);
        $categoryFaq->categories()->attach($category);

        $response = $this->get(route('services.show', $service->catalogPath()));

        $response->assertOk();
        $response->assertDontSee('Вопрос категории');
    }

    public function test_service_page_orders_faq_by_sort_order(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($category);

        $second = $this->makeFaq(['question' => 'Второй вопрос', 'sort_order' => 2]);
        $first = $this->makeFaq(['question' => 'Первый вопрос', 'sort_order' => 1]);
        $service->faqItems()->attach([$second->id, $first->id]);

        $response = $this->get(route('services.show', $service->catalogPath()));

        $response->assertSeeInOrder(['Первый вопрос', 'Второй вопрос']);
    }

    public function test_category_page_shows_faq_bound_to_category(): void
    {
        $category = $this->makeCategory(['name' => 'Выпускные альбомы']);
        $faq = $this->makeFaq(['question' => 'Есть ли скидки?', 'answer' => 'Да, для классов']);
        $faq->categories()->attach($category);

        $response = $this->get(route('services.show', $category->catalogPath()));

        $response->assertOk();
        $response->assertSee('Часто задаваемые вопросы');
        $response->assertSee('Есть ли скидки?');
        $response->assertSee('Да, для классов');
    }

    public function test_category_page_hides_inactive_faq(): void
    {
        $category = $this->makeCategory();
        $faq = $this->makeFaq(['question' => 'Скрытый вопрос категории', 'is_active' => false]);
        $faq->categories()->attach($category);

        $response = $this->get(route('services.show', $category->catalogPath()));

        $response->assertOk();
        $response->assertDontSee('Скрытый вопрос категории');
        $response->assertDontSee('Часто задаваемые вопросы');
    }

    public function test_category_page_shows_only_faq_bound_to_that_category(): void
    {
        $category = $this->makeCategory(['name' => 'Выпускные альбомы']);
        $otherCategory = $this->makeCategory(['name' => 'Свадьбы']);

        $faq = $this->makeFaq(['question' => 'Вопрос этой категории']);
        $faq->categories()->attach($category);

        $otherFaq = $this->makeFaq(['question' => 'Вопрос другой категории']);
        $otherFaq->categories()->attach($otherCategory);

        $response = $this->get(route('services.show', $category->catalogPath()));

        $response->assertSee('Вопрос этой категории');
        $response->assertDontSee('Вопрос другой категории');
    }

    public function test_faq_bound_to_service_and_category_shows_on_both_pages(): void
    {
        $category = $this->makeCategory(['name' => 'Выпускные альбомы', 'slug' => 'vypusknye-albomy']);
        $service = $this->makeService($category, ['title' => 'Классика', 'slug' => 'klassika']);

        $faq = $this->makeFaq(['question' => 'Можно ли добавить развороты?']);
        $faq->services()->attach($service);
        $faq->categories()->attach($category);

        $this->get(route('services.show', $service->catalogPath()))
            ->assertSee('Можно ли добавить развороты?');

        $this->get(route('services.show', $category->catalogPath()))
            ->assertSee('Можно ли добавить развороты?');
    }
}
