<?php

namespace Tests\Feature\Policies;

use App\Models\ArTarget;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArTargetPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach(Role::where('slug', $slug)->first());

        return $user;
    }

    public function test_admin_and_photographer_manage_targets(): void
    {
        $target = ArTarget::factory()->create();

        foreach (['admin', 'photographer'] as $role) {
            $user = $this->userWithRole($role);

            $this->assertTrue($user->can('viewAny', ArTarget::class), $role);
            $this->assertTrue($user->can('create', ArTarget::class), $role);
            $this->assertTrue($user->can('update', $target), $role);
            $this->assertTrue($user->can('delete', $target), $role);
            $this->assertTrue($user->can('deleteAny', ArTarget::class), $role);
        }
    }

    public function test_other_roles_cannot_manage_targets(): void
    {
        $target = ArTarget::factory()->create();

        foreach (['client', 'parent', 'class_manager'] as $role) {
            $user = $this->userWithRole($role);

            $this->assertFalse($user->can('viewAny', ArTarget::class), $role);
            $this->assertFalse($user->can('create', ArTarget::class), $role);
            $this->assertFalse($user->can('update', $target), $role);
            $this->assertFalse($user->can('delete', $target), $role);
        }
    }
}
