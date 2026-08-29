<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Media;
use App\Models\Service;
use App\Models\Video;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryTreeAdminTest extends TestCase
{
    use AdminTestCase;

    public function test_can_create_root_category(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Свадьбы',
                'slug' => 'svadby',
                'type' => 'service',
                'sort_order' => 3,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Свадьбы',
            'slug' => 'svadby',
            'type' => 'service',
            'parent_id' => null,
            'sort_order' => 3,
            'is_published' => true,
        ]);
    }

    public function test_can_create_child_category(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'name' => 'Выпускные альбомы']);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Для школ',
                'slug' => 'dlya-shkol',
                'type' => 'service',
                'parent_id' => $root->id,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'slug' => 'dlya-shkol',
            'type' => 'service',
            'parent_id' => $root->id,
        ]);
    }

    public function test_create_page_prefills_parent_from_query(): void
    {
        $root = Category::factory()->create(['type' => 'service']);

        Livewire::withQueryParams(['parent_id' => (string) $root->id])
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Для детских садов',
                'slug' => 'dlya-sadov',
                'type' => 'service',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'slug' => 'dlya-sadov',
            'parent_id' => $root->id,
        ]);
    }

    public function test_can_change_parent_category(): void
    {
        $rootA = Category::factory()->create(['type' => 'service']);
        $rootB = Category::factory()->create(['type' => 'service']);
        $child = Category::factory()->create(['type' => 'service', 'parent_id' => $rootA->id]);

        Livewire::test(EditCategory::class, ['record' => $child->id])
            ->fillForm(['parent_id' => $rootB->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['id' => $child->id, 'parent_id' => $rootB->id]);
    }

    public function test_can_change_sort_order(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm(['sort_order' => 42])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'sort_order' => 42]);
    }

    public function test_form_blocks_setting_self_as_parent(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm(['parent_id' => $category->id])
            ->call('save');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'parent_id' => null]);
    }

    public function test_form_blocks_creating_cycle(): void
    {
        $root = Category::factory()->create(['type' => 'service']);
        $child = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id]);
        $grandchild = Category::factory()->create(['type' => 'service', 'parent_id' => $child->id]);

        Livewire::test(EditCategory::class, ['record' => $root->id])
            ->fillForm(['parent_id' => $grandchild->id])
            ->call('save');

        $this->assertDatabaseHas('categories', ['id' => $root->id, 'parent_id' => null]);
    }

    public function test_new_fields_are_persisted(): void
    {
        $media = Media::factory()->create(['title' => 'Обложка категории']);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Семейная фотосессия',
                'slug' => 'semejnaya-semka',
                'type' => 'service',
                'cover_media_id' => $media->id,
                'description' => '<p>Съёмка для всей семьи</p>',
                'price_from' => '7000',
                'price_note' => 'до 1 часа съёмки',
                'seo_title' => 'Семейная фотосессия в фотостудии',
                'seo_description' => 'SEO описание семейной съёмки',
                'is_published' => false,
                'sort_order' => 7,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Семейная фотосессия',
            'slug' => 'semejnaya-semka',
            'cover_media_id' => $media->id,
            'description' => '<p>Съёмка для всей семьи</p>',
            'price_from' => '7000.00',
            'price_note' => 'до 1 часа съёмки',
            'seo_title' => 'Семейная фотосессия в фотостудии',
            'seo_description' => 'SEO описание семейной съёмки',
            'is_published' => false,
            'sort_order' => 7,
        ]);
    }

    public function test_videos_can_be_attached_via_form(): void
    {
        $video = Video::factory()->create(['title' => 'Видеоответ о выпускных']);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Выпускные альбомы',
                'slug' => 'vypusknye-albomy',
                'type' => 'service',
                'videos' => [$video->id],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $category = Category::where('slug', 'vypusknye-albomy')->firstOrFail();

        $this->assertDatabaseHas('category_video', [
            'category_id' => $category->id,
            'video_id' => $video->id,
        ]);
    }

    public function test_list_shows_tree_with_paths(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'name' => 'Выпускные альбомы']);
        Category::factory()->create(['type' => 'service', 'name' => 'Для школ', 'parent_id' => $root->id]);
        Category::factory()->create(['type' => 'post', 'name' => 'Новости']);

        $this->get('/admin/categories')
            ->assertSuccessful()
            ->assertSee('Выпускные альбомы')
            ->assertSee('Для школ');
    }

    public function test_table_move_up_action_reorders_siblings(): void
    {
        $root = Category::factory()->create(['type' => 'service', 'sort_order' => 0]);
        $a = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 10, 'name' => 'A']);
        $b = Category::factory()->create(['type' => 'service', 'parent_id' => $root->id, 'sort_order' => 20, 'name' => 'B']);

        Livewire::test(ListCategories::class)
            ->callTableAction('moveUp', $b->id);

        $order = Category::query()
            ->where('parent_id', $root->id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $this->assertSame([$b->id, $a->id], $order);
    }

    public function test_delete_action_is_disabled_for_category_with_services(): void
    {
        $category = Category::factory()->create(['type' => 'service']);
        Service::factory()->create(['category_id' => $category->id]);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->assertActionDisabled('delete');
    }
}
