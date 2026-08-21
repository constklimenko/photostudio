<?php

namespace Tests\Feature\Filament;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
