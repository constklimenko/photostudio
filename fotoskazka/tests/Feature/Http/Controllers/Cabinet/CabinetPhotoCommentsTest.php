<?php

namespace Tests\Feature\Http\Controllers\Cabinet;

use App\Models\Album;
use App\Models\Comment;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CabinetPhotoCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
        Queue::fake();
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

    private function photoForClient(User $client, array $albumAttrs = []): Photo
    {
        $project = Project::factory()->create(['client_id' => $client->id]);

        $album = Album::factory()->create(array_merge(['project_id' => $project->id], $albumAttrs, ['type' => 'client']));

        return Photo::factory()->create(['album_id' => $album->id]);
    }

    private function storeRoute(Photo $photo): string
    {
        return route('cabinet.photo.comments.store', $photo);
    }

    // ── Authentication ─────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $photo = Photo::factory()->create();

        $response = $this->post($this->storeRoute($photo), ['body' => 'Комментарий']);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Client ─────────────────────────────────────────────────────────

    public function test_client_can_comment_photo_of_own_project(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client, ['title' => 'Альбом клиента']);

        $response = $this->actingAs($client)->post(
            $this->storeRoute($photo),
            ['body' => 'Красивый кадр!'],
        );

        $response->assertRedirect(route('cabinet.album', $photo->album).'#photo-'.$photo->id);

        $this->assertDatabaseHas('comments', [
            'user_id' => $client->id,
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'body' => 'Красивый кадр!',
        ]);
    }

    public function test_client_can_comment_photo_of_any_album_type_in_own_project(): void
    {
        $client = $this->userWithRole('client');

        foreach (['project', 'portfolio', 'homepage'] as $type) {
            $photo = $this->photoForClient($client, ['type' => $type]);

            $this->actingAs($client)->post($this->storeRoute($photo), ['body' => 'Кадр '.$type])
                ->assertRedirect(route('cabinet.album', $photo->album).'#photo-'.$photo->id);
        }

        $this->assertDatabaseCount('comments', 3);
    }

    public function test_client_cannot_comment_foreign_photo(): void
    {
        $client = $this->userWithRole('client');
        $otherClient = $this->userWithRole('client');
        $foreignPhoto = $this->photoForClient($otherClient);

        $response = $this->actingAs($client)->post(
            $this->storeRoute($foreignPhoto),
            ['body' => 'Чужое фото'],
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_photo_id_from_form_body_is_ignored(): void
    {
        $client = $this->userWithRole('client');
        $ownPhoto = $this->photoForClient($client);
        $foreignPhoto = $this->photoForClient($this->userWithRole('client'));

        $response = $this->actingAs($client)->post(
            $this->storeRoute($ownPhoto),
            ['body' => 'Комментарий', 'photo_id' => $foreignPhoto->id],
        );

        $response->assertRedirect(route('cabinet.album', $ownPhoto->album).'#photo-'.$ownPhoto->id);

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Photo::class,
            'commentable_id' => $ownPhoto->id,
            'body' => 'Комментарий',
        ]);
        $this->assertDatabaseMissing('comments', ['commentable_id' => $foreignPhoto->id]);
    }

    // ── Class manager ──────────────────────────────────────────────────

    public function test_class_manager_can_comment_photo_in_client_album_of_managed_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $response = $this->actingAs($manager)->post($this->storeRoute($photo), ['body' => 'Менеджер']);

        $response->assertRedirect(route('cabinet.album', $album).'#photo-'.$photo->id);

        $this->assertDatabaseHas('comments', [
            'user_id' => $manager->id,
            'commentable_id' => $photo->id,
            'commentable_type' => Photo::class,
            'body' => 'Менеджер',
        ]);
    }

    public function test_class_manager_cannot_comment_photo_in_project_album_of_managed_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $response = $this->actingAs($manager)->post($this->storeRoute($photo), ['body' => 'Нельзя']);

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_class_manager_cannot_comment_photo_in_client_album_of_foreign_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $otherManager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $otherManager->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $response = $this->actingAs($manager)->post($this->storeRoute($photo), ['body' => 'Чужой проект']);

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Parent ─────────────────────────────────────────────────────────

    public function test_parent_can_comment_photo_in_assigned_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client', 'project_id' => null]);
        $parent->albums()->attach($album->id);
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $response = $this->actingAs($parent)->post($this->storeRoute($photo), ['body' => 'Комментарий родителя']);

        $response->assertRedirect(route('cabinet.album', $album).'#photo-'.$photo->id);

        $this->assertDatabaseHas('comments', [
            'user_id' => $parent->id,
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'body' => 'Комментарий родителя',
        ]);
    }

    public function test_parent_cannot_comment_photo_in_unassigned_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client']);
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $response = $this->actingAs($parent)->post($this->storeRoute($photo), ['body' => 'Не назначен']);

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_parent_cannot_comment_photo_in_assigned_non_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'project']);
        $parent->albums()->attach($album->id);
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $response = $this->actingAs($parent)->post($this->storeRoute($photo), ['body' => 'Не тот тип']);

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Photographer / Admin ───────────────────────────────────────────

    public function test_photographer_can_comment_any_photo(): void
    {
        $photographer = $this->userWithRole('photographer');
        $photo = Photo::factory()->create();

        $response = $this->actingAs($photographer)->post($this->storeRoute($photo), ['body' => 'Фотограф']);

        $response->assertRedirect(route('cabinet.album', $photo->album).'#photo-'.$photo->id);

        $this->assertDatabaseHas('comments', [
            'user_id' => $photographer->id,
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'body' => 'Фотограф',
        ]);
    }

    public function test_admin_can_comment_any_photo(): void
    {
        $admin = $this->userWithRole('admin');
        $photo = Photo::factory()->create();

        $response = $this->actingAs($admin)->post($this->storeRoute($photo), ['body' => 'Админ']);

        $response->assertRedirect(route('cabinet.album', $photo->album).'#photo-'.$photo->id);

        $this->assertDatabaseHas('comments', [
            'user_id' => $admin->id,
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'body' => 'Админ',
        ]);
    }

    // ── No-role ────────────────────────────────────────────────────────

    public function test_user_without_role_cannot_comment(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create();

        $response = $this->actingAs($user)->post($this->storeRoute($photo), ['body' => 'Без роли']);

        $response->assertForbidden();
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Validation ─────────────────────────────────────────────────────

    public function test_body_is_required(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);

        $response = $this->actingAs($client)->post($this->storeRoute($photo), []);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_body_must_be_string(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);

        $response = $this->actingAs($client)->post($this->storeRoute($photo), ['body' => ['не', 'строка']]);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_body_max_length_is_enforced(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);

        $response = $this->actingAs($client)->post(
            $this->storeRoute($photo),
            ['body' => str_repeat('а', 2001)],
        );

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    // ── Placement / IDOR ───────────────────────────────────────────────

    public function test_comment_on_nonexistent_photo_is_404(): void
    {
        $client = $this->userWithRole('client');

        $response = $this->actingAs($client)->post('/cabinet/photos/999/comments', ['body' => 'Тест']);

        $response->assertNotFound();
    }

    // ── Policy matrix through Gate ─────────────────────────────────────

    public function test_photo_comment_gate_follows_photo_view_rights(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');

        $photoA = $this->photoForClient($clientA);
        $photoB = $this->photoForClient($clientB);

        $manager = $this->userWithRole('class_manager');
        $managerProject = Project::factory()->create(['manager_id' => $manager->id]);
        $managedAlbum = Album::factory()->create(['project_id' => $managerProject->id, 'type' => 'client']);
        $managedPhoto = Photo::factory()->create(['album_id' => $managedAlbum->id]);

        $parent = $this->userWithRole('parent');
        $parentAlbum = Album::factory()->create(['type' => 'client']);
        $parent->albums()->attach($parentAlbum->id);
        $parentPhoto = Photo::factory()->create(['album_id' => $parentAlbum->id]);

        $this->assertTrue(Gate::forUser($clientA)->allows('create', [Comment::class, $photoA]));
        $this->assertFalse(Gate::forUser($clientA)->allows('create', [Comment::class, $photoB]));

        $this->assertTrue(Gate::forUser($manager)->allows('create', [Comment::class, $managedPhoto]));
        $this->assertFalse(Gate::forUser($manager)->allows('create', [Comment::class, $photoA]));

        $this->assertTrue(Gate::forUser($parent)->allows('create', [Comment::class, $parentPhoto]));
        $this->assertFalse(Gate::forUser($parent)->allows('create', [Comment::class, $photoA]));

        $this->assertTrue(Gate::forUser($this->userWithRole('photographer'))->allows('create', [Comment::class, $photoA]));
        $this->assertTrue(Gate::forUser($this->userWithRole('admin'))->allows('create', [Comment::class, $photoA]));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('create', [Comment::class, $photoA]));
    }

    // ── Rendering ──────────────────────────────────────────────────────

    public function test_album_gallery_shows_comments_with_author_and_date(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);
        $author = $this->userWithRole('photographer');

        Comment::factory()->create([
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'user_id' => $author->id,
            'body' => 'Видимый комментарий',
        ]);

        $response = $this->actingAs($author)->get(route('cabinet.album', $photo->album));

        $response->assertOk();
        $response->assertSee('Видимый комментарий');
        $response->assertSee($author->name);
        $response->assertSee($photo->comments->first()->created_at->format('d.m.Y'));
    }

    public function test_album_gallery_shows_comment_form_for_authorized_user(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);

        $response = $this->actingAs($client)->get(route('cabinet.album', $photo->album));

        $response->assertOk();
        $response->assertSee(route('cabinet.photo.comments.store', $photo), false);
        $response->assertSee('Оставить комментарий');
        $response->assertSee('photo-comments-'.$photo->id, false);
    }

    public function test_public_portfolio_gallery_does_not_render_comment_ui(): void
    {
        $client = $this->userWithRole('client');
        $album = Album::factory()->create(['type' => 'portfolio', 'is_published' => true, 'slug' => 'portfolio-album']);
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        Comment::factory()->create([
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'user_id' => $client->id,
            'body' => 'Скрытый комментарий',
        ]);

        $response = $this->actingAs($client)->get(route('portfolio.show', ['slug' => $album->slug]));

        $response->assertOk();
        $response->assertDontSee('photo-comments-'.$photo->id, false);
        $response->assertDontSee('Оставить комментарий');
    }

    public function test_photo_comments_are_not_shown_on_project_page(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);

        Comment::factory()->create([
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'user_id' => $client->id,
            'body' => 'Комментарий только к фото',
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.project', $photo->album->project));

        $response->assertOk();
        $response->assertDontSee('Комментарий только к фото');
    }

    public function test_album_gallery_escapes_comment_body_against_xss(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);
        $malicious = '<script>alert("xss")</script>';

        Comment::factory()->create([
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'user_id' => $client->id,
            'body' => $malicious,
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.album', $photo->album));

        $response->assertOk();
        $response->assertSee($malicious);
        $response->assertDontSee($malicious, false);
    }

    public function test_album_gallery_renders_comments_oldest_first(): void
    {
        $client = $this->userWithRole('client');
        $photo = $this->photoForClient($client);

        Comment::factory()->create([
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'user_id' => $client->id,
            'body' => 'Первый комментарий',
            'created_at' => now()->subDays(2),
        ]);
        Comment::factory()->create([
            'commentable_type' => Photo::class,
            'commentable_id' => $photo->id,
            'user_id' => $client->id,
            'body' => 'Второй комментарий',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($client)->get(route('cabinet.album', $photo->album));

        $response->assertOk();
        $response->assertSeeInOrder(['Первый комментарий', 'Второй комментарий']);
    }

    // ── N+1 check ──────────────────────────────────────────────────────

    public function test_album_gallery_with_comments_has_no_n_plus_one(): void
    {
        $client = $this->userWithRole('client');
        $album = Album::factory()->create(['project_id' => Project::factory()->create(['client_id' => $client->id])->id, 'type' => 'client']);

        $author = $this->userWithRole('photographer');

        $photos = Photo::factory()->count(5)->create(['album_id' => $album->id]);
        foreach ($photos as $photo) {
            Comment::factory()->count(3)->create([
                'commentable_type' => Photo::class,
                'commentable_id' => $photo->id,
                'user_id' => $author->id,
            ]);
        }

        \DB::enableQueryLog();

        $this->actingAs($client)->get(route('cabinet.album', $album));

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(15, $queryCount, "Expected ≤15 queries, got {$queryCount}.");
    }
}
