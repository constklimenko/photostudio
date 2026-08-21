<?php

namespace Tests\Feature\Filament;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
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
        $response = $this->get('/admin/categories');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/categories/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $category = Category::factory()->create();

        $response = $this->get("/admin/categories/{$category->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'New Category',
            'slug' => 'new-category',
            'type' => 'service',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'type' => 'service',
        ]);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $category->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $category->delete();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_requires_name(): void
    {
        $category = Category::factory()->create(['name' => '']);

        $this->assertDatabaseHas('categories', ['name' => '']);
    }

    public function test_category_requires_type(): void
    {
        $category = Category::factory()->create(['type' => 'service']);

        $this->assertDatabaseHas('categories', ['type' => 'service']);
    }

    public function test_category_table_displays_data(): void
    {
        $category = Category::factory()->create(['name' => 'Test Category']);

        $response = $this->get('/admin/categories');

        $response->assertSuccessful();
        $this->assertDatabaseHas('categories', ['name' => 'Test Category']);
    }

    public function test_category_type_filter(): void
    {
        $service = Category::factory()->create(['type' => 'service']);
        $post = Category::factory()->create(['type' => 'post']);

        $this->assertDatabaseHas('categories', ['type' => 'service']);
        $this->assertDatabaseHas('categories', ['type' => 'post']);
    }
}
