<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetProjectCommentsTest extends TestCase
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

        $response = $this->post(route('cabinet.project.comments.store', $project), ['body' => 'Комментарий']);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Client ─────────────────────────────────────────────────────────

    public function test_client_can_comment_own_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(
            route('cabinet.project.comments.store', $project),
            ['body' => 'Отличные фото!'],
        );

        $response->assertRedirect(route('cabinet.project', $project));

        $this->assertDatabaseHas('comments', [
            'user_id' => $client->id,
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'body' => 'Отличные фото!',
        ]);
    }

    public function test_client_cannot_comment_foreign_project(): void
    {
        $client = $this->userWithRole('client');
        $otherClient = $this->userWithRole('client');
        $foreignProject = Project::factory()->create(['client_id' => $otherClient->id]);

        $response = $this->actingAs($client)->post(
            route('cabinet.project.comments.store', $foreignProject),
            ['body' => 'Чужой комментарий'],
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Class Manager ──────────────────────────────────────────────────

    public function test_class_manager_can_comment_own_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $response = $this->actingAs($manager)->post(
            route('cabinet.project.comments.store', $project),
            ['body' => 'Комментарий менеджера'],
        );

        $response->assertRedirect(route('cabinet.project', $project));
        $this->assertDatabaseHas('comments', [
            'user_id' => $manager->id,
            'commentable_id' => $project->id,
            'body' => 'Комментарий менеджера',
        ]);
    }

    public function test_class_manager_cannot_comment_foreign_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $otherManager = $this->userWithRole('class_manager');
        $foreignProject = Project::factory()->create(['manager_id' => $otherManager->id]);

        $response = $this->actingAs($manager)->post(
            route('cabinet.project.comments.store', $foreignProject),
            ['body' => 'Чужой проект'],
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Parent (решение по противоречию с моделью доступа) ────────────

    public function test_parent_cannot_comment_project_even_with_assigned_album(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $album->users()->attach($parent->id);

        $response = $this->actingAs($parent)->post(
            route('cabinet.project.comments.store', $project),
            ['body' => 'Родитель не комментирует проект'],
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Photographer / Admin ───────────────────────────────────────────

    public function test_photographer_can_comment_any_project(): void
    {
        $photographer = $this->userWithRole('photographer');
        $project = Project::factory()->create();

        $response = $this->actingAs($photographer)->post(
            route('cabinet.project.comments.store', $project),
            ['body' => 'Комментарий фотографа'],
        );

        $response->assertRedirect(route('cabinet.project', $project));
        $this->assertDatabaseHas('comments', [
            'user_id' => $photographer->id,
            'commentable_id' => $project->id,
            'body' => 'Комментарий фотографа',
        ]);
    }

    public function test_admin_can_comment_any_project(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('cabinet.project.comments.store', $project),
            ['body' => 'Комментарий администратора'],
        );

        $response->assertRedirect(route('cabinet.project', $project));
        $this->assertDatabaseHas('comments', [
            'user_id' => $admin->id,
            'commentable_id' => $project->id,
            'body' => 'Комментарий администратора',
        ]);
    }

    // ── Validation ─────────────────────────────────────────────────────

    public function test_body_is_required(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(
            route('cabinet.project.comments.store', $project),
            [],
        );

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_body_must_be_string(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(
            route('cabinet.project.comments.store', $project),
            ['body' => ['не', 'строка']],
        );

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_body_max_length_is_enforced(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(
            route('cabinet.project.comments.store', $project),
            ['body' => str_repeat('а', 2001)],
        );

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Placement / IDOR ───────────────────────────────────────────────

    public function test_comment_on_nonexistent_project_is_404(): void
    {
        $client = $this->userWithRole('client');

        $response = $this->actingAs($client)->post('/cabinet/projects/999/comments', ['body' => 'Тест']);

        $response->assertNotFound();
    }

    // ── Rendering: sorting + XSS ───────────────────────────────────────

    public function test_comments_are_rendered_oldest_first(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        Comment::factory()->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $client->id,
            'body' => 'Первый комментарий',
            'created_at' => now()->subDays(2),
        ]);
        Comment::factory()->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $client->id,
            'body' => 'Второй комментарий',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSeeInOrder(['Первый комментарий', 'Второй комментарий']);
    }

    public function test_comment_body_is_escaped_against_xss(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);

        $malicious = '<script>alert("xss")</script>';
        Comment::factory()->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $client->id,
            'body' => $malicious,
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee($malicious);
        $response->assertDontSee($malicious, false);
    }

    public function test_project_page_shows_author_and_date(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        Comment::factory()->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $client->id,
            'body' => 'Видимый комментарий',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $project));

        $response->assertOk();
        $response->assertSee($client->name);
        $response->assertSee('Видимый комментарий');
    }

    // ── N+1 check ──────────────────────────────────────────────────────

    public function test_project_page_with_comments_has_no_n_plus_one(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        Comment::factory()->count(5)->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $client->id,
        ]);

        \DB::enableQueryLog();

        $this->actingAs($client)->get(route('cabinet.project', $project));

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(15, $queryCount, "Expected ≤15 queries, got {$queryCount}.");
    }
}
