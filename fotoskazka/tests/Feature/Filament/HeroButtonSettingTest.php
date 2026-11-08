<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Category;
use App\Models\Service;
use Livewire\Livewire;
use Tests\TestCase;

class HeroButtonSettingTest extends TestCase
{
    use AdminTestCase;

    public function test_service_category_edit_page_has_home_button_field(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertFormFieldExists('show_on_home');

        $this->get("/admin/categories/{$category->id}/edit")
            ->assertSuccessful()
            ->assertSee('Выводить в виде кнопки на главной');
    }

    public function test_home_button_field_hidden_for_post_category(): void
    {
        $category = Category::factory()->create(['type' => 'post']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertFormFieldHidden('show_on_home');
    }

    public function test_category_can_be_shown_as_home_button(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'show_on_home' => false,
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => 'service',
                'show_on_home' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'show_on_home' => 1,
        ]);
    }

    public function test_service_edit_page_has_home_button_field(): void
    {
        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertFormFieldExists('show_on_home');

        $this->get("/admin/services/{$service->id}/edit")
            ->assertSuccessful()
            ->assertSee('Выводить в виде кнопки на главной');
    }

    public function test_service_can_be_shown_as_home_button(): void
    {
        $service = Service::factory()->create(['show_on_home' => false]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'title' => $service->title,
                'slug' => $service->slug,
                'show_on_home' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'show_on_home' => 1,
        ]);
    }

    public function test_home_button_can_be_switched_off(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'show_on_home' => true,
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => 'service',
                'show_on_home' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'show_on_home' => 0,
        ]);
    }
}
