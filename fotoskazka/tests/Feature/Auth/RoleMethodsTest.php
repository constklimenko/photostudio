<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_is_admin_returns_true_for_admin(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_photographer(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'photographer')->first());

        $this->assertFalse($user->isAdmin());
    }

    public function test_has_role_returns_true_for_assigned_role(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'client')->first());

        $this->assertTrue($user->hasRole('client'));
    }

    public function test_has_role_returns_false_for_unassigned_role(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'client')->first());

        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_has_any_role_returns_true_when_user_has_one(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'photographer')->first());

        $this->assertTrue($user->hasAnyRole(['admin', 'photographer']));
    }

    public function test_has_any_role_returns_false_when_user_has_none(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'client')->first());

        $this->assertFalse($user->hasAnyRole(['admin', 'photographer']));
    }

    public function test_has_all_roles_returns_true_when_user_has_all(): void
    {
        $user = User::factory()->create();
        $admin = Role::where('slug', 'admin')->first();
        $photo = Role::where('slug', 'photographer')->first();
        $user->roles()->attach([$admin->id, $photo->id]);

        $this->assertTrue($user->hasAllRoles(['admin', 'photographer']));
    }

    public function test_has_all_roles_returns_false_when_missing_one(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        $this->assertFalse($user->hasAllRoles(['admin', 'photographer']));
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        $admin = User::factory()->create(['status' => 'inactive']);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $this->assertFalse($admin->canAccessPanel(Panel::make()->id('admin')));
    }
}
