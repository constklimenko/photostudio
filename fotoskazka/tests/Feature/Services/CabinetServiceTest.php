<?php

namespace Tests\Feature\Services;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\CabinetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetServiceTest extends TestCase
{
    use RefreshDatabase;

    private CabinetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CabinetService;
    }

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

    private function createProjectWithAlbums(int $albumCount = 2, array $projectAttrs = [], array $albumAttrs = []): array
    {
        $project = Project::factory()->create($projectAttrs);
        $albums = Album::factory()
            ->count($albumCount)
            ->create(array_merge(['project_id' => $project->id], $albumAttrs));

        return compact('project', 'albums');
    }

    private function createAlbumWithPhotos(int $photoCount = 3, array $albumAttrs = []): array
    {
        $album = Album::factory()->create($albumAttrs);
        $photos = Photo::factory()
            ->count($photoCount)
            ->create(['album_id' => $album->id]);

        return compact('album', 'photos');
    }

    // ── Admin ─────────────────────────────────────────────────────────────

    public function test_admin_sees_all_projects(): void
    {
        $admin = $this->userWithRole('admin');

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $projects = $this->service->getProjectsForUser($admin);

        $this->assertCount(2, $projects);
        $this->assertTrue($projects->contains($projectA));
        $this->assertTrue($projects->contains($projectB));
    }

    public function test_admin_gets_specific_project(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        $found = $this->service->getProjectForUser($admin, $project->id);

        $this->assertNotNull($found);
        $this->assertEquals($project->id, $found->id);
    }

    // ── Photographer ──────────────────────────────────────────────────────

    public function test_photographer_sees_all_projects(): void
    {
        $photographer = $this->userWithRole('photographer');

        Project::factory()->create();
        Project::factory()->create();

        $projects = $this->service->getProjectsForUser($photographer);

        $this->assertCount(2, $projects);
    }

    // ── Client ────────────────────────────────────────────────────────────

    public function test_client_sees_only_own_projects(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');

        $ownProject = Project::factory()->create(['client_id' => $clientA->id]);
        $foreignProject = Project::factory()->create(['client_id' => $clientB->id]);

        $projects = $this->service->getProjectsForUser($clientA);

        $this->assertCount(1, $projects);
        $this->assertEquals($ownProject->id, $projects->first()->id);
    }

    public function test_client_gets_own_project_by_id(): void
    {
        $client = $this->userWithRole('client');
        $ownProject = Project::factory()->create(['client_id' => $client->id]);

        $found = $this->service->getProjectForUser($client, $ownProject->id);

        $this->assertNotNull($found);
        $this->assertEquals($ownProject->id, $found->id);
    }

    public function test_client_cannot_get_foreign_project_by_id(): void
    {
        $client = $this->userWithRole('client');
        $foreignProject = Project::factory()->create();

        $found = $this->service->getProjectForUser($client, $foreignProject->id);

        $this->assertNull($found);
    }

    public function test_client_sees_all_album_types_of_own_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $clientAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $projectAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $projects = $this->service->getProjectsForUser($client);

        $this->assertCount(1, $projects);
        $this->assertCount(2, $projects->first()->albums);
    }

    public function test_client_does_not_see_albums_without_project(): void
    {
        $client = $this->userWithRole('client');

        $project = Project::factory()->create(['client_id' => $client->id]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $orphanAlbum = Album::factory()->create(['project_id' => null, 'type' => 'client']);

        $projects = $this->service->getProjectsForUser($client);

        $this->assertCount(1, $projects);
        $this->assertNotContains($orphanAlbum->id, $projects->first()->albums->pluck('id'));
    }

    // ── Class Manager ─────────────────────────────────────────────────────

    public function test_class_manager_sees_only_managed_projects(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');

        $ownProject = Project::factory()->create(['manager_id' => $managerA->id]);
        $foreignProject = Project::factory()->create(['manager_id' => $managerB->id]);

        $projects = $this->service->getProjectsForUser($managerA);

        $this->assertCount(1, $projects);
        $this->assertEquals($ownProject->id, $projects->first()->id);
    }

    public function test_class_manager_gets_managed_project_by_id(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $found = $this->service->getProjectForUser($manager, $project->id);

        $this->assertNotNull($found);
        $this->assertEquals($project->id, $found->id);
    }

    public function test_class_manager_cannot_get_foreign_project_by_id(): void
    {
        $manager = $this->userWithRole('class_manager');
        $foreignProject = Project::factory()->create();

        $found = $this->service->getProjectForUser($manager, $foreignProject->id);

        $this->assertNull($found);
    }

    // ── Parent ────────────────────────────────────────────────────────────

    public function test_parent_does_not_see_projects(): void
    {
        $parent = $this->userWithRole('parent');

        Project::factory()->create();

        $projects = $this->service->getProjectsForUser($parent);

        $this->assertCount(0, $projects);
    }

    public function test_parent_gets_only_assigned_client_albums(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();

        $assigned = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $parent->albums()->attach($assigned->id);

        $otherClientAlbum = Album::factory()->create(['type' => 'client']);
        $projectAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $albums = $this->service->getAlbumsForUser($parent);

        $this->assertCount(1, $albums);
        $this->assertEquals($assigned->id, $albums->first()->id);
    }

    public function test_parent_gets_assigned_album_by_id(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();

        $assigned = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $parent->albums()->attach($assigned->id);

        $found = $this->service->getAlbumForUser($parent, $assigned->id);

        $this->assertNotNull($found);
        $this->assertEquals($assigned->id, $found->id);
    }

    public function test_parent_cannot_get_unassigned_album_by_id(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client']);

        $found = $this->service->getAlbumForUser($parent, $album->id);

        $this->assertNull($found);
    }

    public function test_parent_cannot_get_non_client_album_by_id(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'project']);
        $parent->albums()->attach($album->id);

        $found = $this->service->getAlbumForUser($parent, $album->id);

        $this->assertNull($found);
    }

    public function test_parent_gets_album_photos(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();

        $assigned = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $parent->albums()->attach($assigned->id);

        $photos = $this->service->getPhotosForAlbum($parent, $assigned->id);

        $this->assertCount(0, $photos);

        Photo::factory()->count(3)->create(['album_id' => $assigned->id]);

        $photos = $this->service->getPhotosForAlbum($parent, $assigned->id);

        $this->assertCount(3, $photos);
    }

    public function test_parent_cannot_get_photos_of_unassigned_album(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client']);

        Photo::factory()->count(2)->create(['album_id' => $album->id]);

        $photos = $this->service->getPhotosForAlbum($parent, $album->id);

        $this->assertCount(0, $photos);
    }

    public function test_parent_gets_assigned_albums_without_project(): void
    {
        $parent = $this->userWithRole('parent');

        $album = Album::factory()->create(['project_id' => null, 'type' => 'client']);
        $parent->albums()->attach($album->id);

        $albums = $this->service->getAlbumsForUser($parent);

        $this->assertCount(1, $albums);
        $this->assertEquals($album->id, $albums->first()->id);
    }

    // ── Client albums access ──────────────────────────────────────────────

    public function test_client_gets_own_albums_through_albums_method(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $ownAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $foreignAlbum = Album::factory()->create(['type' => 'client']);

        $albums = $this->service->getAlbumsForUser($client);

        $this->assertCount(1, $albums);
        $this->assertEquals($ownAlbum->id, $albums->first()->id);
    }

    public function test_class_manager_gets_only_client_albums_of_managed_project_through_albums_method(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $clientAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $projectAlbum = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);
        $foreignClientAlbum = Album::factory()->create(['type' => 'client']);

        $albums = $this->service->getAlbumsForUser($manager);

        $this->assertCount(1, $albums);
        $this->assertEquals($clientAlbum->id, $albums->first()->id);
    }

    // ── Album photos ──────────────────────────────────────────────────────

    public function test_admin_gets_album_photos(): void
    {
        $admin = $this->userWithRole('admin');
        $album = Album::factory()->create();

        Photo::factory()->count(5)->create(['album_id' => $album->id]);

        $photos = $this->service->getPhotosForAlbum($admin, $album->id);

        $this->assertCount(5, $photos);
    }

    public function test_client_gets_album_photos_of_own_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);

        Photo::factory()->count(4)->create(['album_id' => $album->id]);

        $photos = $this->service->getPhotosForAlbum($client, $album->id);

        $this->assertCount(4, $photos);
    }

    public function test_client_cannot_get_album_photos_of_foreign_project(): void
    {
        $client = $this->userWithRole('client');
        $foreignAlbum = Album::factory()->create(['type' => 'client']);

        Photo::factory()->count(3)->create(['album_id' => $foreignAlbum->id]);

        $photos = $this->service->getPhotosForAlbum($client, $foreignAlbum->id);

        $this->assertCount(0, $photos);
    }

    // ── No N+1 — eager loading verification ───────────────────────────────

    public function test_projects_eager_load_albums_and_photos_count(): void
    {
        $admin = $this->userWithRole('admin');

        $project = Project::factory()->create();
        $albumA = Album::factory()->create(['project_id' => $project->id]);
        $albumB = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Photo::factory()->count(3)->create(['album_id' => $albumA->id]);
        Photo::factory()->count(2)->create(['album_id' => $albumB->id]);

        $projects = $this->service->getProjectsForUser($admin);
        $loaded = $projects->first();

        $this->assertNotNull($loaded->albums);
        $this->assertEquals(2, $loaded->albums_count);
        $this->assertEquals(1, $loaded->client_albums_count);
        $this->assertEquals(5, $loaded->photos_count);
        $this->assertCount(2, $loaded->albums);
    }

    public function test_albums_eager_load_project_cover_users(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $cover = Media::factory()->create();
        $album = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'cover_media_id' => $cover->id,
        ]);
        $parent->albums()->attach($album->id);

        $albums = $this->service->getAlbumsForUser($parent);
        $loaded = $albums->first();

        $this->assertNotNull($loaded->project);
        $this->assertEquals($project->id, $loaded->project->id);
        $this->assertNotNull($loaded->cover);
        $this->assertEquals($cover->id, $loaded->cover->id);
        $this->assertCount(1, $loaded->users);
    }

    // ── Non-role user ─────────────────────────────────────────────────────

    public function test_user_without_role_sees_nothing(): void
    {
        $user = User::factory()->create();

        Project::factory()->create();

        $projects = $this->service->getProjectsForUser($user);

        $this->assertCount(0, $projects);
    }

    // ── Combined roles ────────────────────────────────────────────────────

    public function test_user_with_client_and_class_manager_roles_sees_both(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('class_manager')->id,
        ]);

        $projectAsClient = Project::factory()->create(['client_id' => $user->id]);
        $projectAsManager = Project::factory()->create(['manager_id' => $user->id]);

        $projects = $this->service->getProjectsForUser($user);

        $this->assertCount(2, $projects);
        $this->assertTrue($projects->contains($projectAsClient));
        $this->assertTrue($projects->contains($projectAsManager));
    }

    public function test_user_with_client_and_admin_roles_has_full_access(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('admin')->id,
        ]);

        Project::factory()->create();
        Project::factory()->create();

        $projects = $this->service->getProjectsForUser($user);

        $this->assertCount(2, $projects);
    }

    // ── Album eager loading ───────────────────────────────────────────────

    public function test_projects_eager_load_albums_with_sort_order(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        Album::factory()->create(['project_id' => $project->id, 'sort_order' => 10]);
        Album::factory()->create(['project_id' => $project->id, 'sort_order' => 1]);
        Album::factory()->create(['project_id' => $project->id, 'sort_order' => 5]);

        $projects = $this->service->getProjectsForUser($admin);

        $albumOrders = $projects->first()->albums->pluck('sort_order')->toArray();

        $this->assertEquals([1, 5, 10], $albumOrders);
    }
}
