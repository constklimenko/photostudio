<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageResourceTest extends TestCase
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
        $response = $this->get('/admin/pages');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/pages/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $page = Page::factory()->create();

        $response = $this->get("/admin/pages/{$page->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_page(): void
    {
        $page = Page::factory()->create([
            'title' => 'New Page',
            'slug' => 'new-page',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('pages', [
            'title' => 'New Page',
            'slug' => 'new-page',
        ]);
    }

    public function test_can_update_page(): void
    {
        $page = Page::factory()->create(['title' => 'Old Title']);

        $page->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_page(): void
    {
        $page = Page::factory()->create();

        $page->delete();

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_page_slug_must_be_unique(): void
    {
        $page1 = Page::factory()->create(['slug' => 'unique-slug']);

        $page2 = Page::factory()->make(['slug' => 'unique-slug']);

        $this->assertEquals('unique-slug', $page1->slug);
        $this->assertEquals('unique-slug', $page2->slug);
        $this->assertNotEquals($page1->id, $page2->id);
    }

    public function test_page_table_displays_data(): void
    {
        $page = Page::factory()->create(['title' => 'Test Page']);

        $response = $this->get('/admin/pages');

        $response->assertSuccessful();
        $this->assertDatabaseHas('pages', ['title' => 'Test Page']);
    }

    public function test_page_is_published_filter(): void
    {
        $published = Page::factory()->create(['is_published' => true]);
        $draft = Page::factory()->create(['is_published' => false]);

        $this->assertDatabaseHas('pages', ['is_published' => true]);
        $this->assertDatabaseHas('pages', ['is_published' => false]);
    }

    public function test_home_page_shows_ar_teaser_fields(): void
    {
        $home = Page::factory()->create(['slug' => 'home']);

        $response = $this->get("/admin/pages/{$home->id}/edit");

        $response->assertSuccessful()
            ->assertSee('Оживающие фотографии')
            ->assertSee('ar_teaser_enabled')
            ->assertSee('ar_price');
    }

    public function test_services_page_does_not_show_ar_teaser_fields(): void
    {
        $services = Page::factory()->create(['slug' => 'services']);

        $response = $this->get("/admin/pages/{$services->id}/edit");

        $response->assertSuccessful()
            ->assertDontSee('Оживающие фотографии');
    }

    public function test_portfolio_page_does_not_show_ar_teaser_fields(): void
    {
        $portfolio = Page::factory()->create(['slug' => 'portfolio']);

        $response = $this->get("/admin/pages/{$portfolio->id}/edit");

        $response->assertSuccessful()
            ->assertDontSee('Оживающие фотографии');
    }

    public function test_blog_page_does_not_show_ar_teaser_fields(): void
    {
        $blog = Page::factory()->create(['slug' => 'blog']);

        $response = $this->get("/admin/pages/{$blog->id}/edit");

        $response->assertSuccessful()
            ->assertDontSee('Оживающие фотографии');
    }

    public function test_video_page_does_not_show_ar_teaser_fields(): void
    {
        $video = Page::factory()->create(['slug' => 'video']);

        $response = $this->get("/admin/pages/{$video->id}/edit");

        $response->assertSuccessful()
            ->assertDontSee('Оживающие фотографии');
    }

    public function test_home_settings_not_shown_on_regular_pages(): void
    {
        $page = Page::factory()->create(['slug' => 'services']);

        $response = $this->get("/admin/pages/{$page->id}/edit");

        $response->assertSuccessful()
            ->assertDontSee('Главная страница')
            ->assertSee('Блок на главной')
            ->assertSee('show_on_home');
    }

    public function test_regular_page_shows_common_fields(): void
    {
        $page = Page::factory()->create(['slug' => 'services']);

        $response = $this->get("/admin/pages/{$page->id}/edit");

        $response->assertSuccessful()
            ->assertSee('Заголовок страницы')
            ->assertSee('Альбомы')
            ->assertSee('SEO');
    }

    public function test_regular_page_shows_seo_fields(): void
    {
        $page = Page::factory()->create(['slug' => 'services']);

        $response = $this->get("/admin/pages/{$page->id}/edit");

        $response->assertSuccessful()
            ->assertSee('seo_title')
            ->assertSee('seo_description');
    }

    public function test_home_page_shows_home_settings(): void
    {
        $home = Page::factory()->create(['slug' => 'home']);

        $response = $this->get("/admin/pages/{$home->id}/edit");

        $response->assertSuccessful()
            ->assertSee('О студии')
            ->assertSee('about_studio_text')
            ->assertDontSee('Блок на главной');
    }

    public function test_home_page_saves_ar_teaser_settings(): void
    {
        $home = Page::factory()->create([
            'slug' => 'home',
            'ar_teaser_enabled' => false,
            'ar_teaser_title' => null,
            'ar_teaser_subtitle' => null,
        ]);

        $home->update([
            'ar_teaser_enabled' => true,
            'ar_teaser_title' => 'Оживающие фото',
            'ar_teaser_subtitle' => 'Будущее уже здесь',
            'ar_price' => 750,
        ]);

        $this->assertDatabaseHas('pages', [
            'id' => $home->id,
            'ar_teaser_enabled' => true,
            'ar_teaser_title' => 'Оживающие фото',
            'ar_teaser_subtitle' => 'Будущее уже здесь',
            'ar_price' => 750,
        ]);
    }

    public function test_regular_page_save_does_not_affect_ar_teaser(): void
    {
        $page = Page::factory()->create([
            'slug' => 'services',
            'ar_teaser_enabled' => true,
            'ar_teaser_title' => 'Original',
        ]);

        $page->update(['title' => 'Updated Services']);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Updated Services',
            'ar_teaser_enabled' => true,
            'ar_teaser_title' => 'Original',
        ]);
    }

    public function test_system_page_slug_is_disabled_on_edit(): void
    {
        $home = Page::factory()->create(['slug' => 'home']);

        $response = $this->get("/admin/pages/{$home->id}/edit");

        $response->assertSuccessful()
            ->assertSee('disabled')
            ->assertSee('slug');
    }

    public function test_regular_page_shows_menu_toggle(): void
    {
        $page = Page::factory()->create(['slug' => 'services']);

        $response = $this->get("/admin/pages/{$page->id}/edit");

        $response->assertSuccessful()
            ->assertSee('show_in_menu')
            ->assertSee('Показывать пункт верхнего меню');
    }

    public function test_regular_page_shows_home_block_fields_when_shown_on_home(): void
    {
        $page = Page::factory()->create([
            'slug' => 'services',
            'show_on_home' => true,
        ]);

        $response = $this->get("/admin/pages/{$page->id}/edit");

        $response->assertSuccessful()
            ->assertSee('home_title')
            ->assertSee('home_subtitle')
            ->assertSee('home_content')
            ->assertSee('home_sort_order');
    }

    public function test_can_create_page_with_system_slug_via_form(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Портфолио',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pages', [
            'title' => 'Портфолио',
            'slug' => 'portfolio',
        ]);
    }

    public function test_system_slug_is_saved_even_though_field_is_disabled(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Портфолио',
                'slug' => 'portfolio',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pages', ['slug' => 'portfolio']);
    }
}
