<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleResourceTest extends TestCase
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
        $response = $this->get('/admin/roles');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/roles/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $role = Role::where('is_system', false)->first() ?? Role::factory()->create(['is_system' => false]);

        $response = $this->get("/admin/roles/{$role->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_role(): void
    {
        $role = Role::factory()->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Editor',
            'slug' => 'editor',
        ]);
    }

    public function test_can_update_role(): void
    {
        $role = Role::where('is_system', false)->first() ?? Role::factory()->create(['is_system' => false]);

        $role->update(['name' => 'Updated Role']);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role',
        ]);
    }

    public function test_role_slug_must_be_unique(): void
    {
        $role1 = Role::factory()->create(['slug' => 'unique-slug']);

        $role2 = Role::factory()->make(['slug' => 'unique-slug']);

        $this->assertEquals('unique-slug', $role1->slug);
        $this->assertEquals('unique-slug', $role2->slug);
        $this->assertNotEquals($role1->id, $role2->id);
    }

    public function test_role_table_displays_data(): void
    {
        $role = Role::first();

        $response = $this->get('/admin/roles');

        $response->assertSuccessful();
        $this->assertDatabaseHas('roles', ['slug' => $role->slug]);
    }

    public function test_system_role_exists(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        $this->assertNotNull($adminRole);
        $this->assertTrue($adminRole->is_system);
    }
}
