<?php

namespace Tests\Feature\Policies;

use App\Models\Album;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PhotoPolicyTest extends TestCase
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

    private function photoInAlbum(Album $album): Photo
    {
        return Photo::factory()->create(['album_id' => $album->id]);
    }

    public function test_policy_is_auto_discovered_for_photo(): void
    {
        $admin = $this->userWithRole('admin');
        $photo = Photo::factory()->create();

        $this->assertNotNull(Gate::forUser($admin)->getPolicyFor(Photo::class));
    }

    public function test_admin_can_view_any_photo(): void
    {
        $admin = $this->userWithRole('admin');

        $projectAlbum = Album::factory()->create(['type' => 'project']);
        $clientAlbum = Album::factory()->create(['type' => 'client']);
        $portfolioAlbum = Album::factory()->create(['type' => 'portfolio']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $this->photoInAlbum($projectAlbum)));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $this->photoInAlbum($clientAlbum)));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $this->photoInAlbum($portfolioAlbum)));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $this->photoInAlbum($orphanAlbum)));
    }

    public function test_photographer_can_view_any_photo(): void
    {
        $photographer = $this->userWithRole('photographer');

        $projectAlbum = Album::factory()->create(['type' => 'project']);
        $clientAlbum = Album::factory()->create(['type' => 'client']);
        $portfolioAlbum = Album::factory()->create(['type' => 'portfolio']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($photographer)->allows('view', $this->photoInAlbum($projectAlbum)));
        $this->assertTrue(Gate::forUser($photographer)->allows('view', $this->photoInAlbum($clientAlbum)));
        $this->assertTrue(Gate::forUser($photographer)->allows('view', $this->photoInAlbum($portfolioAlbum)));
        $this->assertTrue(Gate::forUser($photographer)->allows('view', $this->photoInAlbum($orphanAlbum)));
    }

    public function test_client_can_view_photos_of_own_projects(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');

        $projectA = Project::factory()->create(['client_id' => $clientA->id]);
        $projectB = Project::factory()->create(['client_id' => $clientB->id]);

        $albumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'client']);
        $albumB = Album::factory()->create(['project_id' => $projectB->id, 'type' => 'client']);

        $photoA = $this->photoInAlbum($albumA);
        $photoB = $this->photoInAlbum($albumB);

        $this->assertTrue(Gate::forUser($clientA)->allows('view', $photoA));
        $this->assertFalse(Gate::forUser($clientA)->allows('view', $photoB));
        $this->assertFalse(Gate::forUser($clientB)->allows('view', $photoA));
        $this->assertTrue(Gate::forUser($clientB)->allows('view', $photoB));
    }

    public function test_client_can_view_any_album_type_photos_of_own_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $projectAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);
        $portfolioAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'portfolio']);
        $clientAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($client)->allows('view', $this->photoInAlbum($projectAlbum)));
        $this->assertTrue(Gate::forUser($client)->allows('view', $this->photoInAlbum($portfolioAlbum)));
        $this->assertTrue(Gate::forUser($client)->allows('view', $this->photoInAlbum($clientAlbum)));
    }

    public function test_client_without_project_cannot_view_any_photo(): void
    {
        $client = $this->userWithRole('client');

        $album = Album::factory()->create(['type' => 'client']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertFalse(Gate::forUser($client)->allows('view', $this->photoInAlbum($album)));
        $this->assertFalse(Gate::forUser($client)->allows('view', $this->photoInAlbum($orphanAlbum)));
    }

    public function test_class_manager_can_view_photos_of_client_albums_in_managed_project(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');

        $projectA = Project::factory()->create(['manager_id' => $managerA->id]);
        $projectB = Project::factory()->create(['manager_id' => $managerB->id]);

        $clientAlbumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'client']);
        $projectAlbumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'project']);
        $portfolioAlbumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'portfolio']);
        $clientAlbumB = Album::factory()->create(['project_id' => $projectB->id, 'type' => 'client']);

        $this->assertTrue(Gate::forUser($managerA)->allows('view', $this->photoInAlbum($clientAlbumA)));
        $this->assertFalse(Gate::forUser($managerA)->allows('view', $this->photoInAlbum($projectAlbumA)));
        $this->assertFalse(Gate::forUser($managerA)->allows('view', $this->photoInAlbum($portfolioAlbumA)));
        $this->assertFalse(Gate::forUser($managerA)->allows('view', $this->photoInAlbum($clientAlbumB)));
        $this->assertFalse(Gate::forUser($managerB)->allows('view', $this->photoInAlbum($clientAlbumA)));
        $this->assertTrue(Gate::forUser($managerB)->allows('view', $this->photoInAlbum($clientAlbumB)));
    }

    public function test_class_manager_without_project_cannot_view_any_photo(): void
    {
        $manager = $this->userWithRole('class_manager');

        $album = Album::factory()->create(['type' => 'client']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertFalse(Gate::forUser($manager)->allows('view', $this->photoInAlbum($album)));
        $this->assertFalse(Gate::forUser($manager)->allows('view', $this->photoInAlbum($orphanAlbum)));
    }

    public function test_parent_can_view_photos_of_assigned_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();

        $assigned = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $parent->albums()->attach($assigned->id);

        $otherClientAlbum = Album::factory()->create(['type' => 'client']);
        $projectAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);
        $portfolioAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'portfolio']);

        $this->assertTrue(Gate::forUser($parent)->allows('view', $this->photoInAlbum($assigned)));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($otherClientAlbum)));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($projectAlbum)));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($portfolioAlbum)));
    }

    public function test_parent_cannot_view_photos_without_album_user_link(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($album)));
    }

    public function test_parent_cannot_view_photos_of_assigned_non_client_albums(): void
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

        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($projectAlbum)));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($portfolioAlbum)));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($homepageAlbum)));
        $this->assertFalse(Gate::forUser($parent)->allows('view', $this->photoInAlbum($serviceAlbum)));
    }

    public function test_parent_can_view_photos_of_assigned_client_album_without_project(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['project_id' => null, 'type' => 'client']);
        $parent->albums()->attach($album->id);

        $this->assertTrue(Gate::forUser($parent)->allows('view', $this->photoInAlbum($album)));
    }

    public function test_user_with_no_role_cannot_view_photo(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['type' => 'client']);

        $this->assertFalse(Gate::forUser($user)->allows('view', $this->photoInAlbum($album)));
    }

    public function test_guest_cannot_view_photo(): void
    {
        $album = Album::factory()->create(['type' => 'client']);

        $this->assertFalse(Gate::allows('view', $this->photoInAlbum($album)));
    }

    public function test_idor_user_a_cannot_access_user_b_photo(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');

        $projectA = Project::factory()->create(['client_id' => $clientA->id]);
        $projectB = Project::factory()->create(['client_id' => $clientB->id]);

        $albumA = Album::factory()->create(['project_id' => $projectA->id, 'type' => 'client']);
        $albumB = Album::factory()->create(['project_id' => $projectB->id, 'type' => 'client']);

        $photoA = $this->photoInAlbum($albumA);
        $photoB = $this->photoInAlbum($albumB);

        $this->assertTrue(Gate::forUser($clientA)->allows('view', $photoA));
        $this->assertFalse(Gate::forUser($clientA)->allows('view', $photoB));

        $this->assertTrue(Gate::forUser($clientB)->allows('view', $photoB));
        $this->assertFalse(Gate::forUser($clientB)->allows('view', $photoA));

        $this->assertNotEquals($clientA->id, $clientB->id);
        $this->assertNotEquals($photoA->id, $photoB->id);
    }

    public function test_user_with_client_and_class_manager_roles_sees_own_photos_only(): void
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

        $this->assertTrue(Gate::forUser($user)->allows('view', $this->photoInAlbum($ownClientAlbum)));
        $this->assertTrue(Gate::forUser($user)->allows('view', $this->photoInAlbum($managedClientAlbum)));
        $this->assertFalse(Gate::forUser($user)->allows('view', $this->photoInAlbum($managedProjectAlbum)));
        $this->assertFalse(Gate::forUser($user)->allows('view', $this->photoInAlbum($foreignAlbum)));
    }

    public function test_user_with_client_and_admin_roles_has_full_access(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('admin')->id,
        ]);

        $album = Album::factory()->create(['type' => 'client']);

        $this->assertTrue(Gate::forUser($user)->allows('view', $this->photoInAlbum($album)));
    }
}
