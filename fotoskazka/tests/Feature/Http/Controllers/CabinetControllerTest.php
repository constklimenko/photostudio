<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetControllerTest extends TestCase
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

    // ── Authentication ─────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('cabinet.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_cabinet(): void
    {
        $user = User::factory()->create(['name' => 'Тест Пользователь']);

        $response = $this->actingAs($user)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Тест Пользователь');
        $response->assertSee('Личный кабинет');
    }

    // ── Client ─────────────────────────────────────────────────────────

    public function test_client_sees_own_projects(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'title' => 'Мой проект',
            'status' => 'processing',
        ]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Мой проект');
        $response->assertSee('Обработка фотографий');
    }

    public function test_client_does_not_see_foreign_projects(): void
    {
        $client = $this->userWithRole('client');
        $foreignProject = Project::factory()->create(['title' => 'Чужой проект']);

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertDontSee('Чужой проект');
    }

    public function test_client_sees_album_and_photo_counts(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Photo::factory()->count(5)->create(['album_id' => $album->id]);

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('1 '.str('альбом')->plural(1));
        $response->assertSee('5 '.str('фото')->plural(5));
    }

    public function test_client_sees_projects_heading(): void
    {
        $client = $this->userWithRole('client');

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Ваши проекты');
    }

    public function test_client_empty_state(): void
    {
        $client = $this->userWithRole('client');

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Нет доступных проектов');
    }

    // ── Class Manager ──────────────────────────────────────────────────

    public function test_class_manager_sees_managed_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create([
            'manager_id' => $manager->id,
            'title' => 'Проект класса',
            'status' => 'shooting_completed',
        ]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $response = $this->actingAs($manager)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Проект класса');
        $response->assertSee('Фотосъёмка закончена');
    }

    public function test_class_manager_does_not_see_foreign_projects(): void
    {
        $manager = $this->userWithRole('class_manager');
        Project::factory()->create(['title' => 'Чужой проект']);

        $response = $this->actingAs($manager)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertDontSee('Чужой проект');
    }

    public function test_class_manager_sees_only_client_albums_in_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $clientAlbum = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'title' => 'Клиентский альбом',
        ]);
        $projectAlbum = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'project',
            'title' => 'Проектный альбом',
        ]);

        $response = $this->actingAs($manager)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Клиентский альбом');
        $response->assertDontSee('Проектный альбом');
    }

    public function test_class_manager_sees_project_heading(): void
    {
        $manager = $this->userWithRole('class_manager');

        $response = $this->actingAs($manager)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Ваш проект');
    }

    public function test_class_manager_empty_state(): void
    {
        $manager = $this->userWithRole('class_manager');

        $response = $this->actingAs($manager)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Нет доступных проектов');
    }

    // ── Parent ─────────────────────────────────────────────────────────

    public function test_parent_sees_assigned_albums(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $album = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'title' => 'Назначенный альбом',
        ]);
        $parent->albums()->attach($album->id);

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Назначенный альбом');
        $response->assertSee('Назначенные альбомы');
    }

    public function test_parent_does_not_see_projects(): void
    {
        $parent = $this->userWithRole('parent');
        Project::factory()->create(['title' => 'Любой проект']);

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertDontSee('Любой проект');
        $response->assertDontSee('Ваши проекты');
        $response->assertDontSee('Нет доступных проектов');
    }

    public function test_parent_does_not_see_unassigned_albums(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $assignedAlbum = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'title' => 'Назначенный',
        ]);
        $foreignAlbum = Album::factory()->create([
            'type' => 'client',
            'title' => 'Чужой альбом',
        ]);
        $parent->albums()->attach($assignedAlbum->id);

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Назначенный');
        $response->assertDontSee('Чужой альбом');
    }

    public function test_parent_does_not_see_non_client_albums_even_when_assigned(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $projectAlbum = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'project',
            'title' => 'Проектный альбом',
        ]);
        $parent->albums()->attach($projectAlbum->id);

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertDontSee('Проектный альбом');
    }

    public function test_parent_empty_state(): void
    {
        $parent = $this->userWithRole('parent');

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Нет назначенных альбомов');
        $response->assertSee('когда фотограф назначит их вам');
    }

    public function test_parent_sees_album_count(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        Album::factory()->count(3)->create(['project_id' => $project->id, 'type' => 'client'])
            ->each(fn ($album) => $parent->albums()->attach($album->id));

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('3 '.str('альбом')->plural(3));
    }

    // ── Photographer / Admin ───────────────────────────────────────────

    public function test_photographer_sees_all_projects(): void
    {
        $photographer = $this->userWithRole('photographer');
        Project::factory()->create(['title' => 'Проект А']);
        Project::factory()->create(['title' => 'Проект Б']);

        $response = $this->actingAs($photographer)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Проект А');
        $response->assertSee('Проект Б');
    }

    public function test_admin_sees_all_projects(): void
    {
        $admin = $this->userWithRole('admin');
        Project::factory()->create(['title' => 'Проект One']);
        Project::factory()->create(['title' => 'Проект Two']);

        $response = $this->actingAs($admin)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Проект One');
        $response->assertSee('Проект Two');
    }

    public function test_admin_sees_projects_heading(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Проекты');
    }

    public function test_admin_empty_state(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Нет доступных проектов');
    }

    // ── No foreign data leakage ────────────────────────────────────────

    public function test_client_does_not_see_other_client_projects(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');
        Project::factory()->create(['client_id' => $clientA->id, 'title' => 'Проект A']);
        Project::factory()->create(['client_id' => $clientB->id, 'title' => 'Проект B']);

        $response = $this->actingAs($clientA)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Проект A');
        $response->assertDontSee('Проект B');
    }

    public function test_class_manager_does_not_see_other_manager_projects(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');
        Project::factory()->create(['manager_id' => $managerA->id, 'title' => 'Мой проект']);
        Project::factory()->create(['manager_id' => $managerB->id, 'title' => 'Чужой проект']);

        $response = $this->actingAs($managerA)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Мой проект');
        $response->assertDontSee('Чужой проект');
    }

    public function test_parent_does_not_see_other_parent_albums(): void
    {
        $parentA = $this->userWithRole('parent');
        $parentB = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $albumA = Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Альбом A']);
        $albumB = Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Альбом B']);
        $parentA->albums()->attach($albumA->id);
        $parentB->albums()->attach($albumB->id);

        $response = $this->actingAs($parentA)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Альбом A');
        $response->assertDontSee('Альбом B');
    }

    // ── N+1 query check ────────────────────────────────────────────────

    public function test_dashboard_has_no_n_plus_one_queries(): void
    {
        $admin = $this->userWithRole('admin');

        $project = Project::factory()->create();
        $album = Album::factory()->create(['project_id' => $project->id]);
        Media::factory()->create();
        Photo::factory()->count(5)->create(['album_id' => $album->id]);

        \DB::enableQueryLog();

        $this->actingAs($admin)->get(route('cabinet.index'));

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(15, $queryCount, "Expected ≤15 queries, got {$queryCount}. Possible N+1 issue.");
    }

    public function test_parent_dashboard_has_no_n_plus_one_queries(): void
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

        \DB::enableQueryLog();

        $this->actingAs($parent)->get(route('cabinet.index'));

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(15, $queryCount, "Expected ≤15 queries for parent, got {$queryCount}. Possible N+1 issue.");
    }

    // ── Role-specific content ──────────────────────────────────────────

    public function test_client_greeting_contains_name(): void
    {
        $client = $this->userWithRole('client');
        $client->update(['name' => 'Мария Иванова']);

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Мария Иванова');
    }

    public function test_parent_greeting_contains_name(): void
    {
        $parent = $this->userWithRole('parent');
        $parent->update(['name' => 'Ольга Сергеевна']);

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Ольга Сергеевна');
    }

    public function test_project_status_label_is_shown(): void
    {
        $client = $this->userWithRole('client');
        Project::factory()->create([
            'client_id' => $client->id,
            'status' => 'layout_approval',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('Согласование макета');
    }

    public function test_class_manager_sees_only_client_album_count(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $response = $this->actingAs($manager)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee('1 '.str('альбом')->plural(1));
    }
}
