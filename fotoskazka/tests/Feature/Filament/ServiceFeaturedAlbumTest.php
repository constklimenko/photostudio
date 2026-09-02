<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Album;
use App\Models\Service;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceFeaturedAlbumTest extends TestCase
{
    use AdminTestCase;

    public function test_edit_page_has_featured_album_fields(): void
    {
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertFormComponentExists('show_album_photos')
            ->assertFormFieldExists('featured_album_id');

        $response = $this->get("/admin/services/{$service->id}/edit");

        $response->assertSuccessful();
        $response->assertSee('Показать первый альбом блоком с фото');
    }

    public function test_service_can_be_saved_with_featured_album(): void
    {
        $album = Album::factory()->create();
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'show_album_photos' => true,
                'featured_album_id' => $album->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'show_album_photos' => 1,
            'featured_album_id' => $album->id,
        ]);
    }

    public function test_featured_album_can_be_cleared(): void
    {
        $album = Album::factory()->create();
        $service = Service::factory()->create([
            'show_album_photos' => true,
            'featured_album_id' => $album->id,
        ]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'show_album_photos' => false,
                'featured_album_id' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'show_album_photos' => 0,
            'featured_album_id' => null,
        ]);
    }

    public function test_new_service_saves_featured_album_fields(): void
    {
        $album = Album::factory()->create();

        Livewire::test(CreateService::class)
            ->fillForm([
                'title' => 'Новая услуга',
                'slug' => 'novaya-usluga',
                'show_album_photos' => true,
                'featured_album_id' => $album->id,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'slug' => 'novaya-usluga',
            'show_album_photos' => 1,
            'featured_album_id' => $album->id,
        ]);
    }
}
