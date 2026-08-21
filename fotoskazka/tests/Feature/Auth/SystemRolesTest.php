<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $role = Role::where('slug', 'admin')->first();
        $this->assertTrue($role->is_system, 'admin should be system');

        $this->expectException(\LogicException::class);
        $role->delete();
    }

    public function test_system_role_slug_is_protected(): void
    {
        $role = Role::where('slug', 'admin')->first();
        $this->assertTrue($role->is_system, 'admin should be system');

        $this->expectException(\LogicException::class);
        $role->slug = 'superadmin';
        $role->save();
    }

    public function test_user_can_create_custom_role(): void
    {
        $role = Role::create([
            'name' => 'Ретушёр',
            'slug' => 'retoucher',
            'is_system' => false,
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'slug' => 'retoucher',
            'is_system' => false,
        ]);
    }

    public function test_seeded_roles_are_system(): void
    {
        $systemSlugs = ['admin', 'photographer', 'client', 'parent', 'class_manager'];

        foreach ($systemSlugs as $slug) {
            $role = Role::where('slug', $slug)->first();
            $this->assertNotNull($role, "Role {$slug} not found");
            $this->assertTrue($role->is_system, "Role {$slug} is not system");
        }
    }

    private function actingAsAdmin()
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        return $this->actingAs($admin);
    }
}
