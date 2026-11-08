<?php

namespace Tests\Feature\Filament;

use App\Enums\ShootingAlbumDisplay;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Models\Album;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryShootingAlbumTest extends TestCase
{
    use AdminTestCase;

    public function test_edit_page_has_shooting_album_field(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertFormFieldExists('shooting_album_id');

        $response = $this->get("/admin/categories/{$category->id}/edit");

        $response->assertSuccessful();
        $response->assertSee('Альбом «Фото со съёмок»');
    }

    public function test_shooting_album_select_only_lists_behind_the_scenes_albums(): void
    {
        Album::factory()->create(['type' => 'behind_the_scenes', 'title' => 'За кадром']);
        Album::factory()->create(['type' => 'portfolio', 'title' => 'Портфолио']);
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_id',
                checkComponentUsing: function (Select $component): bool {
                    $options = $component->getOptions();

                    return in_array('За кадром', $options, true)
                        && ! in_array('Портфолио', $options, true);
                },
            );
    }

    public function test_shooting_album_select_only_lists_published_albums(): void
    {
        Album::factory()->create(['type' => 'behind_the_scenes', 'title' => 'Опубликованный', 'is_published' => true]);
        Album::factory()->create(['type' => 'behind_the_scenes', 'title' => 'Черновик', 'is_published' => false]);
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_id',
                checkComponentUsing: function (Select $component): bool {
                    $options = $component->getOptions();

                    return in_array('Опубликованный', $options, true)
                        && ! in_array('Черновик', $options, true);
                },
            );
    }

    public function test_edit_page_shows_current_album_even_if_unpublished(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes', 'title' => 'Черновик', 'is_published' => false]);
        $category = Category::factory()->create(['type' => 'service', 'shooting_album_id' => $album->id]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_id',
                checkComponentUsing: fn (Select $component): bool => in_array('Черновик', $component->getOptions(), true),
            );
    }

    public function test_category_can_be_saved_with_shooting_album(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => $category->name,
                'slug' => $category->slug,
                'shooting_album_id' => $album->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'shooting_album_id' => $album->id,
        ]);
    }

    public function test_shooting_album_can_be_cleared(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $category = Category::factory()->create(['type' => 'service', 'shooting_album_id' => $album->id]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => $category->name,
                'slug' => $category->slug,
                'shooting_album_id' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'shooting_album_id' => null,
        ]);
    }

    public function test_new_category_saves_shooting_album(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Новая категория',
                'slug' => 'novaya-kategoriya',
                'type' => 'service',
                'shooting_album_id' => $album->id,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'slug' => 'novaya-kategoriya',
            'shooting_album_id' => $album->id,
        ]);
    }

    public function test_display_field_is_hidden_when_no_album_selected(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_display',
                checkComponentUsing: fn (Component $component): bool => $component->isVisible() === false,
            );
    }

    public function test_display_field_is_visible_when_album_selected(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $category = Category::factory()->create(['type' => 'service', 'shooting_album_id' => $album->id]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_display',
                checkComponentUsing: fn (Select $component): bool => $component->isVisible(),
            );
    }

    public function test_display_field_offers_card_and_grid_options(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_display',
                checkComponentUsing: function (Select $component): bool {
                    $options = $component->getOptions();

                    return in_array('Карточка альбома', $options, true)
                        && in_array('Сетка фотографий', $options, true);
                },
            );
    }

    public function test_new_category_defaults_display_to_card(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Новая категория',
                'slug' => 'novaya-kategoriya',
                'type' => 'service',
                'shooting_album_id' => $album->id,
            ])
            ->assertSet('data.shooting_album_display', ShootingAlbumDisplay::Card->value)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'slug' => 'novaya-kategoriya',
            'shooting_album_id' => $album->id,
            'shooting_album_display' => ShootingAlbumDisplay::Card->value,
        ]);
    }

    public function test_category_can_be_saved_with_grid_display(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => $category->name,
                'slug' => $category->slug,
                'shooting_album_id' => $album->id,
                'shooting_album_display' => ShootingAlbumDisplay::Grid->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'shooting_album_id' => $album->id,
            'shooting_album_display' => ShootingAlbumDisplay::Grid->value,
        ]);
    }

    public function test_clearing_album_resets_display_to_card(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $category = Category::factory()->create([
            'type' => 'service',
            'shooting_album_id' => $album->id,
            'shooting_album_display' => ShootingAlbumDisplay::Grid->value,
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->set('data.shooting_album_id', null)
            ->assertSet('data.shooting_album_display', ShootingAlbumDisplay::Card->value)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'shooting_album_id' => null,
            'shooting_album_display' => ShootingAlbumDisplay::Card->value,
        ]);
    }
}
