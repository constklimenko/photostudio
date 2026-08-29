<?php

namespace Tests\Feature\Services;

use App\Models\Category;
use App\Services\CategoryTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CategoryTreeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_flatten_builds_ordered_tree_with_depth_and_paths(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'name' => 'Выпускные альбомы', 'sort_order' => 0]);
        $child = Category::factory()->create(['type' => 'service', 'name' => 'Для школ', 'parent_id' => $root->id, 'sort_order' => 1]);
        $grandchild = Category::factory()->create(['type' => 'service', 'name' => 'Классика', 'parent_id' => $child->id, 'sort_order' => 0]);
        $sibling = Category::factory()->create(['type' => 'service', 'name' => 'Для детских садов', 'parent_id' => $root->id, 'sort_order' => 2]);

        $tree = app(CategoryTreeService::class)->flatten('service');

        $this->assertSame([$root->id, $child->id, $grandchild->id, $sibling->id], array_keys($tree));
        $this->assertSame(0, $tree[$root->id]['depth']);
        $this->assertSame(1, $tree[$child->id]['depth']);
        $this->assertSame(2, $tree[$grandchild->id]['depth']);
        $this->assertSame('— ', $tree[$child->id]['indent']);
        $this->assertSame('Выпускные альбомы → Для школ → Классика', $tree[$grandchild->id]['pathLabel']);
    }

    public function test_flatten_ignores_post_categories_for_service_type(): void
    {
        Category::factory()->create(['type' => 'post', 'name' => 'Новости']);
        Category::factory()->create(['type' => 'post', 'name' => 'Статьи']);

        $this->assertSame([], app(CategoryTreeService::class)->flatten('service'));
    }

    public function test_options_include_all_service_categories_with_indentation(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'name' => 'Корень']);
        $child = Category::factory()->create(['type' => 'service', 'name' => 'Потомок', 'parent_id' => $root->id]);

        $options = app(CategoryTreeService::class)->options();

        $this->assertArrayHasKey($root->id, $options);
        $this->assertSame('Корень', $options[$root->id]);
        $this->assertSame('— Потомок', $options[$child->id]);
    }

    public function test_options_exclude_self_and_descendants(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'name' => 'Корень']);
        $child = Category::factory()->create(['type' => 'service', 'name' => 'Потомок', 'parent_id' => $root->id]);
        $grandchild = Category::factory()->create(['type' => 'service', 'name' => 'Внук', 'parent_id' => $child->id]);

        $service = app(CategoryTreeService::class);

        $rootOptions = $service->options($root);
        $this->assertArrayNotHasKey($root->id, $rootOptions);
        $this->assertArrayNotHasKey($child->id, $rootOptions);
        $this->assertArrayNotHasKey($grandchild->id, $rootOptions);

        $childOptions = $service->options($child);
        $this->assertArrayHasKey($root->id, $childOptions);
        $this->assertArrayNotHasKey($child->id, $childOptions);
        $this->assertArrayNotHasKey($grandchild->id, $childOptions);
    }

    public function test_move_reorders_siblings_and_rebaselines_sort_order(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'sort_order' => 0]);
        $a = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 10]);
        $b = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 20]);
        $c = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 30]);

        app(CategoryTreeService::class)->move($b, -1);

        $order = Category::query()
            ->where('parent_id', $root->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('id')
            ->all();

        $this->assertSame([$b->id, $a->id, $c->id], $order);
        $this->assertSame([10, 20, 30], Category::query()
            ->where('parent_id', $root->id)
            ->orderBy('sort_order')
            ->pluck('sort_order')
            ->all());
    }

    public function test_move_swaps_forwards(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'sort_order' => 0]);
        $a = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 10]);
        $b = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 20]);

        app(CategoryTreeService::class)->move($a, 1);

        $order = Category::query()
            ->where('parent_id', $root->id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $this->assertSame([$b->id, $a->id], $order);
    }

    public function test_move_at_the_edge_throws(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'sort_order' => 0]);
        $a = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 10]);

        $this->expectException(LogicException::class);

        app(CategoryTreeService::class)->move($a, -1);
    }
}
