<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_and_children_relationship(): void
    {
        $parent = Category::factory()->create(['type' => 'service']);
        $child = Category::factory()->create(['type' => 'service', 'parent_id' => $parent->id]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_multiple_levels_of_nesting(): void
    {
        $root = Category::factory()->create(['type' => 'service']);
        $level1 = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id]);
        $level2 = Category::factory()->create(['type' => 'service', 'parent_id' => $level1->id]);
        $level3 = Category::factory()->create(['type' => 'service', 'parent_id' => $level2->id]);

        $this->assertTrue($level1->parent->is($root));
        $this->assertTrue($level2->parent->is($level1));
        $this->assertTrue($level3->parent->is($level2));
        $this->assertTrue($root->children->contains($level1));
        $this->assertSame([$root->id, $level1->id, $level2->id], array_map(
            fn (Category $c) => $c->id,
            $level3->ancestors()
        ));
        $this->assertSame([$level1->id, $level2->id, $level3->id], array_map(
            fn (Category $c) => $c->id,
            $root->descendants()
        ));
        $this->assertSame([$root->id, $level1->id, $level2->id, $level3->id], array_map(
            fn (Category $c) => $c->id,
            $level3->path(true)
        ));
    }

    public function test_deep_unbounded_tree_traversal(): void
    {
        $root = Category::factory()->create(['type' => 'service']);
        $chain = [$root];
        $previous = $root;

        for ($i = 0; $i < 8; $i++) {
            $previous = Category::factory()->create(['type' => 'service', 'parent_id' => $previous->id]);
            $chain[] = $previous;
        }

        $deep = $previous;

        $this->assertTrue($deep->parent->is($chain[7]));
        $this->assertCount(8, $deep->ancestors());
        $this->assertCount(8, $root->descendants());
        $this->assertSame(
            array_map(fn (Category $c) => $c->id, $chain),
            array_map(fn (Category $c) => $c->id, $deep->path(true)),
        );
    }

    public function test_category_has_many_services(): void
    {
        $category = Category::factory()->create(['type' => 'service']);
        $service = Service::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->services->contains($service));
        $this->assertInstanceOf(Category::class, $service->category);
    }

    public function test_category_belongs_to_cover_media(): void
    {
        $media = Media::factory()->create();
        $category = Category::factory()->create(['type' => 'service', 'cover_media_id' => $media->id]);

        $this->assertTrue($category->cover->is($media));
    }

    public function test_category_cover_is_nullable(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        $this->assertNull($category->cover);
    }

    public function test_service_category_fields_are_persisted(): void
    {
        $media = Media::factory()->create();
        $category = Category::factory()->create([
            'type' => 'service',
            'description' => 'Описание каталога',
            'price_from' => 15000,
            'price_note' => 'от 2 часов съёмки',
            'seo_title' => 'SEO заголовок',
            'seo_description' => 'SEO описание',
            'is_published' => false,
            'cover_media_id' => $media->id,
        ]);

        $fresh = $category->fresh();

        $this->assertSame('Описание каталога', $fresh->description);
        $this->assertSame('15000.00', $fresh->price_from);
        $this->assertSame('от 2 часов съёмки', $fresh->price_note);
        $this->assertSame('SEO заголовок', $fresh->seo_title);
        $this->assertSame('SEO описание', $fresh->seo_description);
        $this->assertFalse($fresh->is_published);
    }

    public function test_service_and_post_categories_work_independently(): void
    {
        $service = Category::factory()->create(['type' => 'service']);
        $post = Category::factory()->create(['type' => 'post']);

        $s = Service::factory()->create(['category_id' => $service->id]);
        $p = Post::factory()->create(['category_id' => $post->id]);

        $this->assertTrue($service->services->contains($s));
        $this->assertTrue($service->posts->isEmpty());
        $this->assertTrue($post->posts->contains($p));
        $this->assertTrue($post->services->isEmpty());
        $this->assertSame('service', $service->type);
        $this->assertSame('post', $post->type);
    }

    public function test_root_category_has_no_parent(): void
    {
        $root = Category::factory()->create(['type' => 'service']);

        $this->assertNull($root->parent);
        $this->assertSame([], $root->ancestors());
    }

    public function test_ancestors_ordered_from_root_to_parent(): void
    {
        $root = Category::factory()->create(['type' => 'service']);
        $b = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id]);
        $c = Category::factory()->create(['type' => 'service', 'parent_id' => $b->id]);

        $this->assertSame([$root->id, $b->id], array_map(fn (Category $i) => $i->id, $c->ancestors()));
        $this->assertSame([$root->id, $b->id, $c->id], array_map(fn (Category $i) => $i->id, $c->path(true)));
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        $this->expectException(LogicException::class);
        $category->update(['parent_id' => $category->id]);
    }

    public function test_setting_a_descendant_as_parent_throws(): void
    {
        $root = Category::factory()->create(['type' => 'service']);
        $child = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id]);
        $grandchild = Category::factory()->create(['type' => 'service', 'parent_id' => $child->id]);

        $this->expectException(LogicException::class);
        $child->update(['parent_id' => $grandchild->id]);
    }

    public function test_detaching_a_category_breaks_the_cycle(): void
    {
        $root = Category::factory()->create(['type' => 'service']);
        $child = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id]);

        $child->update(['parent_id' => null]);

        $this->assertNull($child->fresh()->parent_id);
        $this->assertTrue($root->fresh()->children->isEmpty());

        $child->update(['parent_id' => $root->id]);
        $this->assertTrue($root->fresh()->children->contains($child));
    }
}
