<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\FaqItem;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqItemRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_item_can_be_attached_to_multiple_services(): void
    {
        $faq = FaqItem::factory()->create();
        $services = Service::factory()->count(3)->create();

        $faq->services()->attach($services->pluck('id'));
        $faq->refresh();

        $this->assertCount(3, $faq->services);
        $this->assertTrue($faq->services->contains($services->first()));

        foreach ($services as $service) {
            $this->assertDatabaseHas('faq_item_service', [
                'faq_item_id' => $faq->id,
                'service_id' => $service->id,
            ]);
        }
    }

    public function test_faq_item_can_be_attached_to_multiple_categories(): void
    {
        $faq = FaqItem::factory()->create();
        $categories = Category::factory()->count(2)->create(['type' => 'service']);

        $faq->categories()->attach($categories->pluck('id'));
        $faq->refresh();

        $this->assertCount(2, $faq->categories);
        $this->assertTrue($faq->categories->contains($categories->first()));

        foreach ($categories as $category) {
            $this->assertDatabaseHas('category_faq_item', [
                'faq_item_id' => $faq->id,
                'category_id' => $category->id,
            ]);
        }
    }

    public function test_faq_item_can_be_attached_to_services_and_categories_at_once(): void
    {
        $faq = FaqItem::factory()->create();
        $services = Service::factory()->count(2)->create();
        $category = Category::factory()->create(['type' => 'service']);

        $faq->services()->attach($services->pluck('id'));
        $faq->categories()->attach($category);
        $faq->refresh();

        $this->assertCount(2, $faq->services);
        $this->assertCount(1, $faq->categories);
        $this->assertTrue($faq->categories->first()->is($category));
    }

    public function test_service_has_many_faq_items(): void
    {
        $service = Service::factory()->create();
        $faqs = FaqItem::factory()->count(2)->create();

        $service->faqItems()->attach($faqs->pluck('id'));
        $service->refresh();

        $this->assertCount(2, $service->faqItems);
        $this->assertTrue($service->faqItems->contains($faqs->first()));
    }

    public function test_category_has_many_faq_items(): void
    {
        $category = Category::factory()->create(['type' => 'service']);
        $faq = FaqItem::factory()->create();

        $category->faqItems()->attach($faq);
        $category->refresh();

        $this->assertCount(1, $category->faqItems);
        $this->assertTrue($category->faqItems->first()->is($faq));
    }

    public function test_faq_categories_are_limited_to_service_type(): void
    {
        $faq = FaqItem::factory()->create();
        $serviceCategory = Category::factory()->create(['type' => 'service']);
        $postCategory = Category::factory()->create(['type' => 'post']);

        $faq->categories()->attach([$serviceCategory->id, $postCategory->id]);
        $faq->refresh();

        $categoryIds = $faq->categories->pluck('id');

        $this->assertTrue($categoryIds->contains($serviceCategory->id));
        $this->assertFalse($categoryIds->contains($postCategory->id));
    }

    public function test_relations_can_be_eager_loaded_via_eloquent(): void
    {
        $faq = FaqItem::factory()->create();
        $service = Service::factory()->create();
        $category = Category::factory()->create(['type' => 'service']);

        $faq->services()->attach($service);
        $faq->categories()->attach($category);

        $loaded = FaqItem::with(['services', 'categories'])->findOrFail($faq->id);

        $this->assertTrue($loaded->relationLoaded('services'));
        $this->assertTrue($loaded->relationLoaded('categories'));
        $this->assertTrue($loaded->services->first()->is($service));
        $this->assertTrue($loaded->categories->first()->is($category));
    }

    public function test_deleting_faq_item_removes_both_pivot_records(): void
    {
        $faq = FaqItem::factory()->create();
        $service = Service::factory()->create();
        $category = Category::factory()->create(['type' => 'service']);

        $faq->services()->attach($service);
        $faq->categories()->attach($category);

        $faq->delete();

        $this->assertDatabaseMissing('faq_item_service', ['faq_item_id' => $faq->id]);
        $this->assertDatabaseMissing('category_faq_item', ['faq_item_id' => $faq->id]);
    }

    public function test_deleting_service_removes_pivot_record(): void
    {
        $faq = FaqItem::factory()->create();
        $service = Service::factory()->create();

        $faq->services()->attach($service);
        $this->assertDatabaseHas('faq_item_service', ['service_id' => $service->id]);

        $service->delete();

        $this->assertDatabaseMissing('faq_item_service', ['service_id' => $service->id]);
        $this->assertDatabaseHas('faq_items', ['id' => $faq->id]);
    }

    public function test_deleting_category_removes_pivot_record(): void
    {
        $faq = FaqItem::factory()->create();
        $category = Category::factory()->create(['type' => 'service']);

        $faq->categories()->attach($category);
        $this->assertDatabaseHas('category_faq_item', ['category_id' => $category->id]);

        $category->delete();

        $this->assertDatabaseMissing('category_faq_item', ['category_id' => $category->id]);
        $this->assertDatabaseHas('faq_items', ['id' => $faq->id]);
    }
}
