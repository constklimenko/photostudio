<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\FaqItems\Pages\CreateFaqItem;
use App\Filament\Resources\FaqItems\Pages\EditFaqItem;
use App\Models\Category;
use App\Models\FaqItem;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FaqItemResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($admin);
    }

    public function test_list_page_renders(): void
    {
        $response = $this->get('/admin/faq-items');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/faq-items/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $item = FaqItem::factory()->create();

        $response = $this->get("/admin/faq-items/{$item->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_faq_item(): void
    {
        $item = FaqItem::factory()->create([
            'question' => 'How much?',
            'answer' => 'It depends.',
        ]);

        $this->assertDatabaseHas('faq_items', [
            'question' => 'How much?',
            'answer' => 'It depends.',
        ]);
    }

    public function test_can_update_faq_item(): void
    {
        $item = FaqItem::factory()->create(['question' => 'Old Question']);

        $item->update(['question' => 'Updated Question']);

        $this->assertDatabaseHas('faq_items', [
            'id' => $item->id,
            'question' => 'Updated Question',
        ]);
    }

    public function test_can_delete_faq_item(): void
    {
        $item = FaqItem::factory()->create();

        $item->delete();

        $this->assertDatabaseMissing('faq_items', ['id' => $item->id]);
    }

    public function test_faq_item_table_displays_data(): void
    {
        $item = FaqItem::factory()->create(['question' => 'Test Question']);

        $response = $this->get('/admin/faq-items');

        $response->assertSuccessful();
        $this->assertDatabaseHas('faq_items', ['question' => 'Test Question']);
    }

    public function test_faq_form_persists_service_and_category_links(): void
    {
        $services = Service::factory()->count(2)->create();
        $category = Category::factory()->create(['type' => 'service']);

        Livewire::test(CreateFaqItem::class)
            ->fillForm([
                'question' => 'Можно ли выбрать услуги?',
                'answer' => 'Да.',
                'services' => $services->pluck('id')->all(),
                'categories' => [$category->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $faq = FaqItem::where('question', 'Можно ли выбрать услуги?')->firstOrFail();

        $this->assertCount(2, $faq->services);
        $this->assertCount(1, $faq->categories);
    }

    public function test_faq_form_stores_html_answer(): void
    {
        $html = '<p>Ответ с <strong>жирным</strong> текстом</p>';

        Livewire::test(CreateFaqItem::class)
            ->fillForm([
                'question' => 'Как оформить заказ?',
                'answer' => $html,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $faq = FaqItem::where('question', 'Как оформить заказ?')->firstOrFail();

        $this->assertSame($html, $faq->answer);
        $this->assertStringContainsString('<strong>жирным</strong>', $faq->answer);
    }

    public function test_faq_html_answer_survives_edit(): void
    {
        $faq = FaqItem::factory()->create([
            'answer' => '<p>Старый <em>ответ</em></p>',
        ]);

        Livewire::test(EditFaqItem::class, ['record' => $faq->getKey()])
            ->fillForm(['answer' => '<p>Новый <strong>ответ</strong></p>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('<p>Новый <strong>ответ</strong></p>', $faq->refresh()->answer);
    }

    public function test_faq_form_saves_multiple_services(): void
    {
        $services = Service::factory()->count(3)->create();

        Livewire::test(CreateFaqItem::class)
            ->fillForm([
                'question' => 'Вопрос про несколько услуг',
                'answer' => '<p>Ответ</p>',
                'services' => $services->pluck('id')->all(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $faq = FaqItem::where('question', 'Вопрос про несколько услуг')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            $services->pluck('id')->all(),
            $faq->services->pluck('id')->all(),
        );
    }

    public function test_faq_form_saves_multiple_service_categories(): void
    {
        $categories = Category::factory()->count(2)->create(['type' => 'service']);

        Livewire::test(CreateFaqItem::class)
            ->fillForm([
                'question' => 'Вопрос про несколько категорий',
                'answer' => '<p>Ответ</p>',
                'categories' => $categories->pluck('id')->all(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $faq = FaqItem::where('question', 'Вопрос про несколько категорий')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            $categories->pluck('id')->all(),
            $faq->categories->pluck('id')->all(),
        );
    }

    public function test_faq_form_updates_links_on_edit(): void
    {
        $faq = FaqItem::factory()->create();

        $oldService = Service::factory()->create();
        $newServices = Service::factory()->count(2)->create();

        $oldCategory = Category::factory()->create(['type' => 'service']);
        $newCategory = Category::factory()->create(['type' => 'service']);

        $faq->services()->attach($oldService);
        $faq->categories()->attach($oldCategory);

        Livewire::test(EditFaqItem::class, ['record' => $faq->getKey()])
            ->fillForm([
                'services' => $newServices->pluck('id')->all(),
                'categories' => [$newCategory->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $faq->refresh();

        $this->assertEqualsCanonicalizing(
            $newServices->pluck('id')->all(),
            $faq->services->pluck('id')->all(),
        );
        $this->assertFalse($faq->services->contains($oldService));
        $this->assertSame([$newCategory->id], $faq->categories->pluck('id')->all());
        $this->assertFalse($faq->categories->contains($oldCategory));
    }

    public function test_faq_form_removes_links_on_edit(): void
    {
        $faq = FaqItem::factory()->create();
        $service = Service::factory()->create();
        $category = Category::factory()->create(['type' => 'service']);

        $faq->services()->attach($service);
        $faq->categories()->attach($category);

        Livewire::test(EditFaqItem::class, ['record' => $faq->getKey()])
            ->fillForm([
                'services' => [],
                'categories' => [],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $faq->refresh();

        $this->assertCount(0, $faq->services);
        $this->assertCount(0, $faq->categories);
    }
}
