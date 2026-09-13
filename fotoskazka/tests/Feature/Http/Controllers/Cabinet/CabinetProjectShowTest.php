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

class CabinetProjectShowTest extends TestCase
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
        $project = Project::factory()->create();

        $response = $this->get(route('cabinet.project', $project));

        $response->assertRedirect(route('login'));
    }

    // ── Client ─────────────────────────────────────────────────────────

    public function test_client_can_view_own_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'title' => 'Мой проект',
            'status' => 'processing',
        ]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Клиентский альбом']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project', 'title' => 'Проектный альбом']);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Мой проект');
        $response->assertSee('Обработка фотографий');
        $response->assertSee('Клиентский альбом');
        $response->assertSee('Проектный альбом');
    }

    public function test_client_cannot_view_foreign_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['title' => 'Чужой проект']);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertForbidden();
    }

    public function test_client_sees_project_description(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'description' => 'Описание проекта для клиента',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Описание проекта для клиента');
    }

    public function test_client_sees_shooting_date(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'shooting_date' => '2025-06-15',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('15.06.2025');
    }

    public function test_client_sees_album_photo_count(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Photo::factory()->count(7)->create(['album_id' => $album->id]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('7 '.str('фото')->plural(7));
    }

    public function test_client_sees_album_cover(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $cover = Media::factory()->create();
        Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'cover_media_id' => $cover->id,
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('img');
        $response->assertSee($cover->getThumbnailUrl());
    }

    public function test_client_sees_empty_state_when_no_albums(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Нет доступных альбомов');
    }

    public function test_client_sees_back_link_to_projects(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee(route('cabinet.projects'));
    }

    // ── Class Manager ──────────────────────────────────────────────────

    public function test_class_manager_can_view_own_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create([
            'manager_id' => $manager->id,
            'title' => 'Проект класса',
            'status' => 'shooting_completed',
        ]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Клиентский альбом']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project', 'title' => 'Проектный альбом']);

        $response = $this->actingAs($manager)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Проект класса');
        $response->assertSee('Фотосъёмка закончена');
        $response->assertSee('Клиентский альбом');
        $response->assertDontSee('Проектный альбом');
    }

    public function test_class_manager_cannot_view_foreign_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['title' => 'Чужой проект']);

        $response = $this->actingAs($manager)->get(route('cabinet.project', $project));

        $response->assertForbidden();
    }

    public function test_class_manager_sees_only_client_type_albums(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Альбом клиент']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project', 'title' => 'Альбом проект']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'portfolio', 'title' => 'Альбом портфолио']);

        $response = $this->actingAs($manager)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Альбом клиент');
        $response->assertDontSee('Альбом проект');
        $response->assertDontSee('Альбом портфолио');
    }

    public function test_class_manager_sees_album_count_for_client_albums_only(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $response = $this->actingAs($manager)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('1 '.str('альбом')->plural(1));
    }

    // ── Parent ─────────────────────────────────────────────────────────

    public function test_parent_cannot_view_project(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();

        $response = $this->actingAs($parent)->get(route('cabinet.project', $project));

        $response->assertForbidden();
    }

    // ── Photographer / Admin ───────────────────────────────────────────

    public function test_photographer_can_view_any_project(): void
    {
        $photographer = $this->userWithRole('photographer');
        $project = Project::factory()->create([
            'title' => 'Любой проект',
            'status' => 'completed',
        ]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Клиентский']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project', 'title' => 'Проектный']);

        $response = $this->actingAs($photographer)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Любой проект');
        $response->assertSee('Проект завершён');
        $response->assertSee('Клиентский');
        $response->assertSee('Проектный');
    }

    public function test_admin_can_view_any_project(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create(['title' => 'Проект Admin']);

        $response = $this->actingAs($admin)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Проект Admin');
    }

    public function test_photographer_sees_all_album_types(): void
    {
        $photographer = $this->userWithRole('photographer');
        $project = Project::factory()->create(['manager_id' => $photographer->id]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Клиент']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'project', 'title' => 'Проект']);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'portfolio', 'title' => 'Портфолио']);

        $response = $this->actingAs($photographer)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Клиент');
        $response->assertSee('Проект');
        $response->assertSee('Портфолио');
    }

    // ── No foreign data leakage (IDOR) ─────────────────────────────────

    public function test_client_cannot_access_other_client_project(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');
        $projectB = Project::factory()->create([
            'client_id' => $clientB->id,
            'title' => 'Проект B',
        ]);

        $response = $this->actingAs($clientA)->get(route('cabinet.project', $projectB));

        $response->assertForbidden();
    }

    public function test_class_manager_cannot_access_other_manager_project(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');
        $projectB = Project::factory()->create([
            'manager_id' => $managerB->id,
            'title' => 'Проект менеджера B',
        ]);

        $response = $this->actingAs($managerA)->get(route('cabinet.project', $projectB));

        $response->assertForbidden();
    }

    public function test_client_does_not_see_foreign_albums_in_own_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $ownAlbum = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'title' => 'Мой альбом',
        ]);

        $otherClient = $this->userWithRole('client');
        $otherProject = Project::factory()->create(['client_id' => $otherClient->id]);
        $foreignAlbum = Album::factory()->create([
            'project_id' => $otherProject->id,
            'type' => 'client',
            'title' => 'Чужой альбом',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Мой альбом');
        $response->assertDontSee('Чужой альбом');
    }

    // ── N+1 query check ────────────────────────────────────────────────

    public function test_project_page_has_no_n_plus_one_queries(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        $albumA = Album::factory()->create(['project_id' => $project->id, 'cover_media_id' => Media::factory()->create()->id]);
        $albumB = Album::factory()->create(['project_id' => $project->id]);
        Photo::factory()->count(5)->create(['album_id' => $albumA->id]);
        Photo::factory()->count(3)->create(['album_id' => $albumB->id]);

        \DB::enableQueryLog();

        $this->actingAs($admin)->get(route('cabinet.project', $project));

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(15, $queryCount, "Expected ≤15 queries, got {$queryCount}. Possible N+1 issue.");
    }

    // ── Album gallery link placeholder ─────────────────────────────────

    public function test_album_card_shows_gallery_link(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Открыть →');
    }

    // ── Status badge ───────────────────────────────────────────────────

    public function test_project_status_label_is_shown(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => 'layout_approval',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee('Согласование макета');
    }

    // ── Navigation from projects list ──────────────────────────────────

    public function test_projects_list_links_to_project_page(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id, 'title' => 'Ссылка на проект']);

        $response = $this->actingAs($client)->get(route('cabinet.projects'));

        $response->assertOk();
        $response->assertSee(route('cabinet.project', $project));
    }
}
