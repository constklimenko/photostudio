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

class CabinetProjectsTest extends TestCase
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
        $response = $this->get(route('cabinet.projects'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_projects_page(): void
    {
        $user = User::factory()->create(['name' => 'Тест Пользователь']);

        $response = $this->actingAs($user)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Тест Пользователь');
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

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Мой проект');
        $response->assertSee('Обработка фотографий');
    }

    public function test_client_does_not_see_foreign_projects(): void
    {
        $client = $this->userWithRole('client');
        Project::factory()->create(['title' => 'Чужой проект']);

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertDontSee('Чужой проект');
    }

    public function test_client_sees_album_and_photo_counts(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Photo::factory()->count(5)->create(['album_id' => $album->id]);

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('1 '.str('альбом')->plural(1));
        $response->assertSee('5 '.str('фото')->plural(5));
    }

    public function test_client_empty_state(): void
    {
        $client = $this->userWithRole('client');

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Нет доступных проектов');
    }

    public function test_client_sees_heading(): void
    {
        $client = $this->userWithRole('client');

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Мои проекты');
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

        $response = $this->actingAs($manager)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Проект класса');
        $response->assertSee('Фотосъёмка закончена');
    }

    public function test_class_manager_does_not_see_foreign_projects(): void
    {
        $manager = $this->userWithRole('class_manager');
        Project::factory()->create(['title' => 'Чужой проект']);

        $response = $this->actingAs($manager)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertDontSee('Чужой проект');
    }

    public function test_class_manager_sees_only_client_album_count(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $response = $this->actingAs($manager)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('1 '.str('альбом')->plural(1));
    }

    public function test_class_manager_sees_heading(): void
    {
        $manager = $this->userWithRole('class_manager');

        $response = $this->actingAs($manager)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Ваш проект');
    }

    public function test_class_manager_empty_state(): void
    {
        $manager = $this->userWithRole('class_manager');

        $response = $this->actingAs($manager)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Нет доступных проектов');
    }

    // ── Parent ─────────────────────────────────────────────────────────

    public function test_parent_does_not_see_projects(): void
    {
        $parent = $this->userWithRole('parent');
        Project::factory()->create(['title' => 'Любой проект']);

        $response = $this->actingAs($parent)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertDontSee('Любой проект');
    }

    public function test_parent_sees_empty_state(): void
    {
        $parent = $this->userWithRole('parent');

        $response = $this->actingAs($parent)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Нет доступных проектов');
    }

    // ── Photographer / Admin ───────────────────────────────────────────

    public function test_photographer_sees_all_projects(): void
    {
        $photographer = $this->userWithRole('photographer');
        Project::factory()->create(['title' => 'Проект А']);
        Project::factory()->create(['title' => 'Проект Б']);

        $response = $this->actingAs($photographer)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Проект А');
        $response->assertSee('Проект Б');
    }

    public function test_admin_sees_all_projects(): void
    {
        $admin = $this->userWithRole('admin');
        Project::factory()->create(['title' => 'Проект One']);
        Project::factory()->create(['title' => 'Проект Two']);

        $response = $this->actingAs($admin)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Проект One');
        $response->assertSee('Проект Two');
    }

    // ── No foreign data leakage (IDOR) ─────────────────────────────────

    public function test_client_does_not_see_other_client_projects(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');
        Project::factory()->create(['client_id' => $clientA->id, 'title' => 'Проект A']);
        Project::factory()->create(['client_id' => $clientB->id, 'title' => 'Проект B']);

        $response = $this->actingAs($clientA)->get(route('cabinet.projects'));

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

        $response = $this->actingAs($managerA)->get(route('cabinet.projects'));

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

        $response = $this->actingAs($parentA)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertDontSee('Альбом B');
    }

    // ── N+1 query check ────────────────────────────────────────────────

    public function test_projects_page_has_no_n_plus_one_queries(): void
    {
        $admin = $this->userWithRole('admin');

        $project = Project::factory()->create();
        $album = Album::factory()->create(['project_id' => $project->id]);
        Media::factory()->create();
        Photo::factory()->count(5)->create(['album_id' => $album->id]);

        \DB::enableQueryLog();

        $this->actingAs($admin)->get(route('cabinet.projects'));

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(15, $queryCount, "Expected ≤15 queries, got {$queryCount}. Possible N+1 issue.");
    }

    // ── Status badge ───────────────────────────────────────────────────

    public function test_project_status_label_is_shown(): void
    {
        $client = $this->userWithRole('client');
        Project::factory()->create([
            'client_id' => $client->id,
            'status' => 'layout_approval',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Согласование макета');
    }

    // ── Navigation link from dashboard ─────────────────────────────────

    public function test_dashboard_shows_projects_link(): void
    {
        $client = $this->userWithRole('client');
        Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee(route('cabinet.projects'));
        $response->assertSee('Все проекты');
    }

    public function test_parent_dashboard_does_not_show_projects_link(): void
    {
        $parent = $this->userWithRole('parent');

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertDontSee(route('cabinet.projects'));
    }

    // ── Projects list uses ProjectPolicy::view ─────────────────────────

    public function test_client_sees_only_projects_passing_policy(): void
    {
        $client = $this->userWithRole('client');
        $ownProject = Project::factory()->create(['client_id' => $client->id, 'title' => 'Свой']);
        $foreignProject = Project::factory()->create(['title' => 'Чужой']);

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Свой');
        $response->assertDontSee('Чужой');
    }

    public function test_class_manager_sees_only_projects_passing_policy(): void
    {
        $manager = $this->userWithRole('class_manager');
        $ownProject = Project::factory()->create(['manager_id' => $manager->id, 'title' => 'Мой']);
        $foreignProject = Project::factory()->create(['title' => 'Чужой']);

        $response = $this->actingAs($manager)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Мой');
        $response->assertDontSee('Чужой');
    }

    // ── Combined roles ─────────────────────────────────────────────────

    public function test_user_with_client_and_admin_roles_sees_all(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('admin')->id,
        ]);

        Project::factory()->create(['title' => 'Проект A']);
        Project::factory()->create(['title' => 'Проект B']);

        $response = $this->actingAs($user)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee('Проект A');
        $response->assertSee('Проект B');
    }
}
