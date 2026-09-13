<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Album;
use App\Models\Category;
use App\Models\Service;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceCtaButtonTest extends TestCase
{
    use AdminTestCase;

    public function test_edit_service_page_has_cta_fields(): void
    {
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertFormFieldExists('cta_album_id')
            ->assertFormFieldExists('cta_button_text');
    }

    public function test_service_can_be_saved_with_cta_fields(): void
    {
        $album = Album::factory()->create();
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'cta_album_id' => $album->id,
                'cta_button_text' => 'Посмотреть варианты обложек',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'cta_album_id' => $album->id,
            'cta_button_text' => 'Посмотреть варианты обложек',
        ]);
    }

    public function test_service_cta_fields_can_be_cleared(): void
    {
        $album = Album::factory()->create();
        $service = Service::factory()->create([
            'cta_album_id' => $album->id,
            'cta_button_text' => 'Старый текст',
        ]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'cta_album_id' => null,
                'cta_button_text' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'cta_album_id' => null,
            'cta_button_text' => null,
        ]);
    }

    public function test_new_service_saves_cta_fields(): void
    {
        $album = Album::factory()->create();

        Livewire::test(CreateService::class)
            ->fillForm([
                'title' => 'Новая услуга',
                'slug' => 'novaya-usluga',
                'cta_album_id' => $album->id,
                'cta_button_text' => 'Посмотреть варианты',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'slug' => 'novaya-usluga',
            'cta_album_id' => $album->id,
            'cta_button_text' => 'Посмотреть варианты',
        ]);
    }

    public function test_edit_category_page_has_cta_fields(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertFormFieldExists('cta_album_id')
            ->assertFormFieldExists('cta_button_text');
    }

    public function test_category_can_be_saved_with_cta_fields(): void
    {
        $album = Album::factory()->create();
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => $category->type,
                'cta_album_id' => $album->id,
                'cta_button_text' => 'Смотреть обложки',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'cta_album_id' => $album->id,
            'cta_button_text' => 'Смотреть обложки',
        ]);
    }
}
