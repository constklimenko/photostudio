<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_has_access_to_panel(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $this->assertTrue($admin->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_photographer_has_no_access_to_panel(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach(Role::where('slug', 'photographer')->first());

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_client_has_no_access_to_panel(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach(Role::where('slug', 'client')->first());

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_parent_has_no_access_to_panel(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach(Role::where('slug', 'parent')->first());

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_class_manager_has_no_access_to_panel(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach(Role::where('slug', 'class_manager')->first());

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/cabinet');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_cabinet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/cabinet');

        $response->assertOk();
        $response->assertSee('Личный кабинет');
    }
}
