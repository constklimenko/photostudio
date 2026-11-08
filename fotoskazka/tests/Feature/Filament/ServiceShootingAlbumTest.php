<?php

namespace Tests\Feature\Filament;

use App\Enums\ShootingAlbumDisplay;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Album;
use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceShootingAlbumTest extends TestCase
{
    use AdminTestCase;

    public function test_edit_page_has_shooting_album_field(): void
    {
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertFormFieldExists('shooting_album_id');

        $response = $this->get("/admin/services/{$service->id}/edit");

        $response->assertSuccessful();
        $response->assertSee('Альбом «Фото со съёмок»');
    }

    public function test_shooting_album_select_only_lists_behind_the_scenes_albums(): void
    {
        Album::factory()->create(['type' => 'behind_the_scenes', 'title' => 'За кадром']);
        Album::factory()->create(['type' => 'portfolio', 'title' => 'Портфолио']);
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
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
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
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
        $service = Service::factory()->create(['shooting_album_id' => $album->id]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_id',
                checkComponentUsing: fn (Select $component): bool => in_array('Черновик', $component->getOptions(), true),
            );
    }

    public function test_service_can_be_saved_with_shooting_album(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'shooting_album_id' => $album->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'shooting_album_id' => $album->id,
        ]);
    }

    public function test_shooting_album_can_be_cleared(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $service = Service::factory()->create(['shooting_album_id' => $album->id]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'shooting_album_id' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'shooting_album_id' => null,
        ]);
    }

    public function test_new_service_saves_shooting_album(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);

        Livewire::test(CreateService::class)
            ->fillForm([
                'title' => 'Новая услуга',
                'slug' => 'novaya-usluga',
                'shooting_album_id' => $album->id,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'slug' => 'novaya-usluga',
            'shooting_album_id' => $album->id,
        ]);
    }

    public function test_display_field_is_hidden_when_no_album_selected(): void
    {
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_display',
                checkComponentUsing: fn (Component $component): bool => $component->isVisible() === false,
            );
    }

    public function test_display_field_is_visible_when_album_selected(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $service = Service::factory()->create(['shooting_album_id' => $album->id]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_display',
                checkComponentUsing: fn (Select $component): bool => $component->isVisible(),
            );
    }

    public function test_display_field_offers_card_and_grid_options(): void
    {
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertSchemaComponentExists(
                'shooting_album_display',
                checkComponentUsing: function (Select $component): bool {
                    $options = $component->getOptions();

                    return in_array('Карточка альбома', $options, true)
                        && in_array('Сетка фотографий', $options, true);
                },
            );
    }

    public function test_new_service_defaults_display_to_card(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);

        Livewire::test(CreateService::class)
            ->fillForm([
                'title' => 'Новая услуга',
                'slug' => 'novaya-usluga',
                'shooting_album_id' => $album->id,
            ])
            ->assertSet('data.shooting_album_display', ShootingAlbumDisplay::Card->value)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'slug' => 'novaya-usluga',
            'shooting_album_id' => $album->id,
            'shooting_album_display' => ShootingAlbumDisplay::Card->value,
        ]);
    }

    public function test_service_can_be_saved_with_grid_display(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'shooting_album_id' => $album->id,
                'shooting_album_display' => ShootingAlbumDisplay::Grid->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'shooting_album_id' => $album->id,
            'shooting_album_display' => ShootingAlbumDisplay::Grid->value,
        ]);
    }

    public function test_clearing_album_resets_display_to_card(): void
    {
        $album = Album::factory()->create(['type' => 'behind_the_scenes']);
        $service = Service::factory()->create([
            'shooting_album_id' => $album->id,
            'shooting_album_display' => ShootingAlbumDisplay::Grid->value,
        ]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->set('data.shooting_album_id', null)
            ->assertSet('data.shooting_album_display', ShootingAlbumDisplay::Card->value)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'shooting_album_id' => null,
            'shooting_album_display' => ShootingAlbumDisplay::Card->value,
        ]);
    }
}
