<?php

namespace Tests\Feature\Policies;

use App\Models\Album;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AlbumPolicyTest extends TestCase
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

    public function test_policy_is_auto_discovered_for_album(): void
    {
        $admin = $this->userWithRole('admin');

        $this->assertNotNull(Gate::forUser($admin)->getPolicyFor(Album::class));
    }

    public function test_view_any_allows_admin_and_photographer(): void
    {
        $this->assertTrue(Gate::forUser($this->userWithRole('admin'))->allows('viewAny', Album::class));
        $this->assertTrue(Gate::forUser($this->userWithRole('photographer'))->allows('viewAny', Album::class));
    }

    public function test_view_any_denied_to_role_restricted_users(): void
    {
        $this->assertFalse(Gate::forUser($this->userWithRole('client'))->allows('viewAny', Album::class));
        $this->assertFalse(Gate::forUser($this->userWithRole('class_manager'))->allows('viewAny', Album::class));
        $this->assertFalse(Gate::forUser($this->userWithRole('parent'))->allows('viewAny', Album::class));
    }

    public function test_admin_can_view_any_album(): void
    {
        $admin = $this->userWithRole('admin');

        $projectAlbum = Album::factory()->create(['type' => 'project']);
        $clientAlbum = Album::factory()->create(['type' => 'client']);
        $portfolioAlbum = Album::factory()->create(['type' => 'portfolio']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $projectAlbum));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $clientAlbum));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $portfolioAlbum));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $orphanAlbum));
    }

    public function test_photographer_can_view_any_album(): void
    {
        $photographer = $this->userWithRole('photographer');

        $projectAlbum = Album::factory()->create(['type' => 'project']);
        $clientAlbum = Album::factory()->create(['type' => 'client']);
        $portfolioAlbum = Album::factory()->create(['type' => 'portfolio']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($photographer)->allows('view', $projectAlbum));
        $this->assertTrue(Gate::forUser($photographer)->allows('view', $clientAlbum));
        $this->assertTrue(Gate::forUser($photographer)->allows('view', $portfolioAlbum));
        $this->assertTrue(Gate::forUser($photographer)->allows('view', $orphanAlbum));
    }

    public function test_client_can_view_albums_of_own_projects(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');

        $projectA = Project::factory()->create(['client_id' => $clientA->id]);
        $projectB = Project::factory()->create(['client_id' => $clientB->id]);

        $albumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'client']);
        $albumB = Album::factory()->create(['project_id' => $projectB->id, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($clientA)->allows('view', $albumA));
        $this->assertFalse(Gate::forUser($clientA)->allows('view', $albumB));
        $this->assertFalse(Gate::forUser($clientB)->allows('view', $albumA));
        $this->assertTrue(Gate::forUser($clientB)->allows('view', $albumB));
    }

    public function test_client_can_view_any_album_type_of_own_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $projectAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);
        $portfolioAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'portfolio']);
        $clientAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($client)->allows('view', $projectAlbum));
        $this->assertTrue(Gate::forUser($client)->allows('view', $portfolioAlbum));
        $this->assertTrue(Gate::forUser($client)->allows('view', $clientAlbum));
    }

    public function test_client_without_project_cannot_view_any_album(): void
    {
        $client = $this->userWithRole('client');

        $ownProjectAlbum = Album::factory()->create(['type' => 'client']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertFalse(Gate::forUser($client)->allows('view', $ownProjectAlbum));
        $this->assertFalse(Gate::forUser($client)->allows('view', $orphanAlbum));
    }

    public function test_class_manager_can_view_only_client_albums_of_managed_project(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');

        $projectA = Project::factory()->create(['manager_id' => $managerA->id]);
        $projectB = Project::factory()->create(['manager_id' => $managerB->id]);

        $clientAlbumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'client']);
        $projectAlbumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'project']);
        $portfolioAlbumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'portfolio']);
        $clientAlbumB = Album::factory()->create(['project_id' => $projectB->id, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($managerA)->allows('view', $clientAlbumA));
        $this->assertFalse(Gate::forUser($managerA)->allows('view', $projectAlbumA));
        $this->assertFalse(Gate::forUser($managerA)->allows('view', $portfolioAlbumA));
        $this->assertFalse(Gate::forUser($managerA)->allows('view', $clientAlbumB));
        $this->assertFalse(Gate::forUser($managerB)->allows('view', $clientAlbumA));
        $this->assertTrue(Gate::forUser($managerB)->allows('view', $clientAlbumB));
    }

    public function test_class_manager_without_project_cannot_view_any_album(): void
    {
        $manager = $this->userWithRole('class_manager');

        $clientAlbum = Album::factory()->create(['type' => 'client']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertFalse(Gate::forUser($manager)->allows('view', $clientAlbum));
        $this->assertFalse(Gate::forUser($manager)->allows('view', $orphanAlbum));
    }

    public function test_parent_can_view_only_assigned_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();

        $assigned = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $parent->albums()->attach($assigned->id);

        $otherClientAlbum = Album::factory()->create(['type' => 'client']);
        $projectAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);
        $portfolioAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'portfolio']);

        $this->assertTrue(Gate::forUser($parent)->allows('view', $assigned));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $otherClientAlbum));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $projectAlbum));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $portfolioAlbum));
    }

    public function test_parent_cannot_view_client_album_without_album_user_link(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertFalse(Gate::forUser($parent)->allows('view', $album));
    }

    public function test_parent_cannot_view_assigned_non_client_albums(): void
    {
        $parent = $this->userWithRole('parent');

        $projectAlbum = Album::factory()->create(['type' => 'project']);
        $portfolioAlbum = Album::factory()->create(['type' => 'portfolio']);
        $homepageAlbum = Album::factory()->create(['type' => 'homepage']);
        $serviceAlbum = Album::factory()->create(['type' => 'service']);
        $parent->albums()->attach([
            $projectAlbum->id,
            $portfolioAlbum->id,
            $homepageAlbum->id,
            $serviceAlbum->id,
        ]);

        $this->assertFalse(Gate::forUser($parent)->allows('view', $projectAlbum));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $portfolioAlbum));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $homepageAlbum));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $serviceAlbum));
    }

    public function test_parent_can_view_assigned_client_album_without_project(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['project_id' => null, 'type' => 'client']);
        $parent->albums()->attach($album->id);

        $this->assertTrue(Gate::forUser($parent)->allows('view', $album));
    }

    public function test_user_with_no_role_cannot_view_album(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['type' => 'client']);

        $this->assertFalse(Gate::forUser($user)->allows('view', $album));
    }

    public function test_guest_cannot_view_album(): void
    {
        $album = Album::factory()->create(['type' => 'client']);

        $this->assertFalse(Gate::allows('view', $album));
    }

    public function test_user_with_client_and_class_manager_roles_sees_own_albums_only(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('class_manager')->id,
        ]);

        $projectAsClient = Project::factory()->create(['client_id' => $user->id]);
        $projectAsManager = Project::factory()->create(['manager_id' => $user->id]);

        $ownClientAlbum = Album::factory()->create(['project_id' => $projectAsClient->id, 'type' => 'client']);
        $managedClientAlbum = Album::factory()->create(['project_id' => $projectAsManager->id, 'type' => 'client']);
        $managedProjectAlbum = Album::factory()->create(['project_id' => $projectAsManager->id, 'type' => 'project']);
        $foreignAlbum = Album::factory()->create(['type' => 'client']);

        $this->assertTrue(Gate::forUser($user)->allows('view', $ownClientAlbum));
        $this->assertTrue(Gate::forUser($user)->allows('view', $managedClientAlbum));
        $this->assertFalse(Gate::forUser($user)->allows('view', $managedProjectAlbum));
        $this->assertFalse(Gate::forUser($user)->allows('view', $foreignAlbum));
    }

    public function test_user_with_client_role_and_admin_role_has_full_access(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('admin')->id,
        ]);

        $album = Album::factory()->create(['type' => 'client']);

        $this->assertTrue(Gate::forUser($user)->allows('view', $album));
    }
}
