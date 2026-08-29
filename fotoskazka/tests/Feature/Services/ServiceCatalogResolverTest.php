<?php

namespace Tests\Feature\Services;

use App\Models\Category;
use App\Models\Service;
use App\Services\ServiceCatalogResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogResolverTest extends TestCase
{
    use RefreshDatabase;

    private Category $albums;

    private Category $schools;

    private Service $classic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->albums = Category::factory()->create([
            'type' => 'service',
            'slug' => 'vypusknye-albomy',
            'parent_id' => null,
            'is_published' => true,
        ]);

        $this->schools = Category::factory()->create([
            'type' => 'service',
            'slug' => 'dlya-shkol',
            'parent_id' => $this->albums->id,
            'is_published' => true,
        ]);

        $this->classic = Service::factory()->create([
            'slug' => 'klassika',
            'category_id' => $this->schools->id,
            'is_published' => true,
        ]);
    }

    public function test_resolves_root_category(): void
    {
        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy']);

        $this->assertInstanceOf(Category::class, $result);
        $this->assertTrue($result->is($this->albums));
    }

    public function test_resolves_nested_category(): void
    {
        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'dlya-shkol']);

        $this->assertInstanceOf(Category::class, $result);
        $this->assertTrue($result->is($this->schools));
    }

    public function test_resolves_service_inside_nested_category(): void
    {
        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'dlya-shkol', 'klassika']);

        $this->assertInstanceOf(Service::class, $result);
        $this->assertTrue($result->is($this->classic));
    }

    public function test_resolves_root_level_service(): void
    {
        $service = Service::factory()->create([
            'slug' => 'individualnaya-semka',
            'category_id' => null,
            'is_published' => true,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['individualnaya-semka']);

        $this->assertInstanceOf(Service::class, $result);
        $this->assertTrue($result->is($service));
    }

    public function test_prefers_category_when_slug_matches_both(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'slug' => 'klassika',
            'parent_id' => $this->schools->id,
            'is_published' => true,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'dlya-shkol', 'klassika']);

        $this->assertInstanceOf(Category::class, $result);
        $this->assertTrue($result->is($category));
    }

    public function test_returns_null_for_wrong_middle_segment(): void
    {
        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'wrong', 'klassika']);

        $this->assertNull($result);
    }

    public function test_returns_null_for_wrong_category_slug(): void
    {
        $result = app(ServiceCatalogResolver::class)->resolve(['wrong']);

        $this->assertNull($result);
    }

    public function test_returns_null_for_unknown_service_in_valid_chain(): void
    {
        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'dlya-shkol', 'unknown']);

        $this->assertNull($result);
    }

    public function test_returns_null_when_root_category_slug_is_a_service_elsewhere(): void
    {
        Service::factory()->create([
            'slug' => 'vypusknye-albomy',
            'category_id' => null,
            'is_published' => true,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy']);

        $this->assertInstanceOf(Category::class, $result);
    }

    public function test_returns_null_for_unpublished_root_category(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'slug' => 'hidden',
            'is_published' => false,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['hidden']);

        $this->assertNull($result);
    }

    public function test_returns_null_for_unpublished_nested_category(): void
    {
        $hidden = Category::factory()->create([
            'type' => 'service',
            'slug' => 'hidden',
            'parent_id' => $this->albums->id,
            'is_published' => false,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'hidden']);

        $this->assertNull($result);
    }

    public function test_returns_null_for_unpublished_service(): void
    {
        Service::factory()->create([
            'slug' => 'hidden-service',
            'category_id' => $this->schools->id,
            'is_published' => false,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'dlya-shkol', 'hidden-service']);

        $this->assertNull($result);
    }

    public function test_returns_null_for_service_in_unpublished_category(): void
    {
        $hidden = Category::factory()->create([
            'type' => 'service',
            'slug' => 'hidden',
            'parent_id' => $this->albums->id,
            'is_published' => false,
        ]);

        Service::factory()->create([
            'slug' => 'closed',
            'category_id' => $hidden->id,
            'is_published' => true,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['vypusknye-albomy', 'hidden', 'closed']);

        $this->assertNull($result);
    }

    public function test_returns_null_for_empty_segments(): void
    {
        $this->assertNull(app(ServiceCatalogResolver::class)->resolve([]));
    }

    public function test_does_not_resolve_post_categories(): void
    {
        Category::factory()->create([
            'type' => 'post',
            'slug' => 'blog-tip',
            'parent_id' => null,
            'is_published' => true,
        ]);

        $result = app(ServiceCatalogResolver::class)->resolve(['blog-tip']);

        $this->assertNull($result);
    }
}
