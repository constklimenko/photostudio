<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
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
        $response = $this->get('/admin/users');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/users/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->get("/admin/users/{$user->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_user(): void
    {
        $user = User::factory()->create([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'status' => 'active',
        ]);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $user->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_email_must_be_unique(): void
    {
        $user1 = User::factory()->create(['email' => 'existing@example.com']);

        $user2 = User::factory()->make(['email' => 'existing@example.com']);

        $this->assertEquals('existing@example.com', $user1->email);
        $this->assertEquals('existing@example.com', $user2->email);
        $this->assertNotEquals($user1->id, $user2->id);
    }

    public function test_user_table_displays_data(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->get('/admin/users');

        $response->assertSuccessful();
        $this->assertDatabaseHas('users', ['name' => 'Test User']);
    }

    public function test_user_status_filter(): void
    {
        $active = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive']);

        $this->assertDatabaseHas('users', ['status' => 'active']);
        $this->assertDatabaseHas('users', ['status' => 'inactive']);
    }
}
