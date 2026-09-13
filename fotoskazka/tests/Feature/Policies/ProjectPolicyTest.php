<?php

namespace Tests\Feature\Policies;

use App\Models\Album;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $slug): Role
    {
        return Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->role($slug)->id);

        return $user;
    }

    public function test_policy_is_auto_discovered_for_project(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        $this->assertNotNull(Gate::forUser($admin)->getPolicyFor(Project::class));
    }

    public function test_admin_can_view_any_project(): void
    {
        $admin = $this->userWithRole('admin');
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $projectA));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $projectB));
    }

    public function test_photographer_can_view_any_project(): void
    {
        $photographer = $this->userWithRole('photographer');
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $this->assertTrue(Gate::forUser($photographer)->allows('view', $projectA));
        $this->assertTrue(Gate::forUser($photographer)->allows('view', $projectB));
    }

    public function test_client_can_view_only_own_projects(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');

        $projectA = Project::factory()->create(['client_id' => $clientA->id]);
        $projectB = Project::factory()->create(['client_id' => $clientB->id]);

        $this->assertTrue(Gate::forUser($clientA)->allows('view', $projectA));
        $this->assertFalse(Gate::forUser($clientA)->allows('view', $projectB));
        $this->assertFalse(Gate::forUser($clientB)->allows('view', $projectA));
        $this->assertTrue(Gate::forUser($clientB)->allows('view', $projectB));
    }

    public function test_class_manager_can_view_only_managed_projects(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');

        $projectA = Project::factory()->create(['manager_id' => $managerA->id]);
        $projectB = Project::factory()->create(['manager_id' => $managerB->id]);

        $this->assertTrue(Gate::forUser($managerA)->allows('view', $projectA));
        $this->assertFalse(Gate::forUser($managerA)->allows('view', $projectB));
        $this->assertFalse(Gate::forUser($managerB)->allows('view', $projectA));
        $this->assertTrue(Gate::forUser($managerB)->allows('view', $projectB));
    }

    public function test_parent_cannot_view_project_even_with_assigned_album(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $parent->albums()->attach($album->id);

        $this->assertFalse(Gate::forUser($parent)->allows('view', $project));
    }

    public function test_guest_cannot_view_project(): void
    {
        $project = Project::factory()->create();

        $this->assertFalse(Gate::allows('view', $project));
    }

    public function test_user_without_role_cannot_view_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('view', $project));
    }

    public function test_user_with_client_and_class_manager_roles_views_both_assigned_projects(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('class_manager')->id,
        ]);

        $projectAsClient = Project::factory()->create(['client_id' => $user->id]);
        $projectAsManager = Project::factory()->create(['manager_id' => $user->id]);
        $foreignProject = Project::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('view', $projectAsClient));
        $this->assertTrue(Gate::forUser($user)->allows('view', $projectAsManager));
        $this->assertFalse(Gate::forUser($user)->allows('view', $foreignProject));
    }

    public function test_user_with_client_role_and_admin_role_has_full_access(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('admin')->id,
        ]);

        $project = Project::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('view', $project));
    }

    public function test_client_cannot_view_project_without_client_assignment(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create();

        $this->assertFalse(Gate::forUser($client)->allows('view', $project));
    }
}
