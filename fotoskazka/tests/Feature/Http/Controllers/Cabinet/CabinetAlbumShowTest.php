<?php

namespace Tests\Feature\Http\Controllers\Cabinet;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CabinetAlbumShowTest extends TestCase
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

    private function albumForClient(User $client, array $albumAttrs = [], array $projectAttrs = []): Album
    {
        $project = Project::factory()->create(array_merge(['client_id' => $client->id], $projectAttrs));

        return Album::factory()->create(array_merge(['project_id' => $project->id], $albumAttrs));
    }

    // ── Authentication ─────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $album = Album::factory()->create(['type' => 'client']);

        $response = $this->get(route('cabinet.album', $album));

        $response->assertRedirect(route('login'));
    }

    // ── Client ─────────────────────────────────────────────────────────

    public function test_client_can_view_own_album_gallery(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, [
            'title' => 'Выпускной альбом',
            'description' => 'Описание альбома',
            'type' => 'client',
        ], ['title' => 'Проект 11 А']);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee('Выпускной альбом');
        $response->assertSee('Описание альбома');
        $response->assertSee('Проект 11 А');
    }

    public function test_client_sees_photos_and_media_urls(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);
        $mediaA = Media::factory()->create();
        $mediaB = Media::factory()->create();
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $mediaA->id, 'caption' => 'Первый кадр']);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $mediaB->id, 'caption' => 'Второй кадр']);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee(route('media.display', ['media' => $mediaA->id]));
        $response->assertSee(route('media.display', ['media' => $mediaB->id]));
        $response->assertSee(route('media.lightbox', ['media' => $mediaA->id]));
        $response->assertSee(route('media.lightbox', ['media' => $mediaB->id]));
        $response->assertSee($mediaA->getUrl());
        $response->assertSee($mediaB->getUrl());
    }

    public function test_remote_media_uses_original_route_url(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);
        $media = Media::factory()->create([
            'disk' => 'yandex_disk',
            'file_path' => 'originals/photo.jpg',
        ]);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee(route('media.original', ['media' => $media->id]));
    }

    public function test_client_cannot_view_foreign_album(): void
    {
        $client = $this->userWithRole('client');
        $otherClient = $this->userWithRole('client');
        $foreignAlbum = $this->albumForClient($otherClient, ['type' => 'client']);

        $response = $this->actingAs($client)->get(route('cabinet.album', $foreignAlbum));

        $response->assertForbidden();
    }

    public function test_client_can_view_any_album_type_of_own_project(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'project']);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
    }

    public function test_client_sees_empty_state_when_album_has_no_photos(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee('В альбоме пока нет фотографий');
    }

    public function test_client_does_not_see_foreign_media(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);
        Photo::factory()->create(['album_id' => $album->id]);

        $otherClient = $this->userWithRole('client');
        $foreignAlbum = $this->albumForClient($otherClient, ['type' => 'client']);
        $foreignMedia = Media::factory()->create();
        Photo::factory()->create(['album_id' => $foreignAlbum->id, 'media_id' => $foreignMedia->id]);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertDontSee(route('media.lightbox', ['media' => $foreignMedia->id]));
    }

    // ── Class Manager ──────────────────────────────────────────────────

    public function test_class_manager_can_view_client_album_of_own_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Альбом класса']);

        $response = $this->actingAs($manager)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee('Альбом класса');
    }

    public function test_class_manager_cannot_view_project_type_album_of_own_project(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'project']);

        $response = $this->actingAs($manager)->get(route('cabinet.album', $album));

        $response->assertForbidden();
    }

    public function test_class_manager_cannot_view_client_album_of_foreign_project(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');
        $projectB = Project::factory()->create(['manager_id' => $managerB->id]);
        $albumB = Album::factory()->create(['project_id' => $projectB->id, 'type' => 'client']);

        $response = $this->actingAs($managerA)->get(route('cabinet.album', $albumB));

        $response->assertForbidden();
    }

    // ── Parent ─────────────────────────────────────────────────────────

    public function test_parent_can_view_assigned_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'title' => 'Назначенный альбом']);
        $parent->albums()->attach($album->id);

        $response = $this->actingAs($parent)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee('Назначенный альбом');
    }

    public function test_parent_cannot_view_unassigned_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client']);

        $response = $this->actingAs($parent)->get(route('cabinet.album', $album));

        $response->assertForbidden();
    }

    public function test_parent_cannot_view_assigned_non_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'project']);
        $parent->albums()->attach($album->id);

        $response = $this->actingAs($parent)->get(route('cabinet.album', $album));

        $response->assertForbidden();
    }

    public function test_parent_can_view_assigned_client_album_without_project(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client', 'project_id' => null]);
        $parent->albums()->attach($album->id);

        $response = $this->actingAs($parent)->get(route('cabinet.album', $album));

        $response->assertOk();
    }

    // ── Photographer / Admin ───────────────────────────────────────────

    public function test_photographer_can_view_any_album(): void
    {
        $photographer = $this->userWithRole('photographer');
        $album = Album::factory()->create(['type' => 'client', 'title' => 'Альбом для фотографа']);

        $response = $this->actingAs($photographer)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee('Альбом для фотографа');
    }

    public function test_admin_can_view_any_album(): void
    {
        $admin = $this->userWithRole('admin');
        $album = Album::factory()->create(['type' => 'project', 'title' => 'Альбом админа']);

        $response = $this->actingAs($admin)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee('Альбом админа');
    }

    // ── No-role / Combined roles ───────────────────────────────────────

    public function test_user_without_role_cannot_view_album(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['type' => 'client']);

        $response = $this->actingAs($user)->get(route('cabinet.album', $album));

        $response->assertForbidden();
    }

    public function test_user_with_client_role_has_full_access(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach([
            $this->role('client')->id,
            $this->role('admin')->id,
        ]);
        $album = Album::factory()->create(['type' => 'client']);

        $response = $this->actingAs($user)->get(route('cabinet.album', $album));

        $response->assertOk();
    }

    // ── IDOR ───────────────────────────────────────────────────────────

    public function test_client_cannot_access_other_client_album_by_id(): void
    {
        $clientA = $this->userWithRole('client');
        $clientB = $this->userWithRole('client');
        $albumB = $this->albumForClient($clientB, ['type' => 'client', 'title' => 'Альбом B']);
        Photo::factory()->count(3)->create(['album_id' => $albumB->id]);

        $this->actingAs($clientA)->get(route('cabinet.album', $albumB))
            ->assertForbidden();
    }

    public function test_class_manager_cannot_access_other_manager_album_by_id(): void
    {
        $managerA = $this->userWithRole('class_manager');
        $managerB = $this->userWithRole('class_manager');
        $projectB = Project::factory()->create(['manager_id' => $managerB->id]);
        $albumB = Album::factory()->create(['project_id' => $projectB->id, 'type' => 'client']);
        Photo::factory()->count(2)->create(['album_id' => $albumB->id]);

        $this->actingAs($managerA)->get(route('cabinet.album', $albumB))
            ->assertForbidden();
    }

    public function test_parent_cannot_access_other_parent_album_by_id(): void
    {
        $parentA = $this->userWithRole('parent');
        $parentB = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $albumA = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $albumB = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        $parentA->albums()->attach($albumA->id);
        $parentB->albums()->attach($albumB->id);

        $this->actingAs($parentA)->get(route('cabinet.album', $albumB))
            ->assertForbidden();

        $this->actingAs($parentA)->get(route('cabinet.album', $albumA))
            ->assertOk();
    }

    // ── Direct storage URL leak regression ──────────────────────────────

    public function test_private_album_page_never_emits_direct_storage_urls(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);

        $originalPath = 'images/secret_original.jpg';
        $thumbnailPath = 'images/secret_thumb.webp';

        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => $originalPath,
            'thumbnail_path' => $thumbnailPath,
        ]);

        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee(route('media.original', ['media' => $media->id]));
        $response->assertSee(route('media.display', ['media' => $media->id]));
        $response->assertSee(route('media.lightbox', ['media' => $media->id]));
        $response->assertDontSee('/storage/'.$originalPath, false);
        $response->assertDontSee('/storage/thumbnails/'.$thumbnailPath, false);
    }

    public function test_private_album_cover_thumbnail_is_proxied_not_direct(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client', 'project_id' => null]);

        $cover = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'images/cover.jpg',
            'thumbnail_path' => 'images/cover_thumb.webp',
        ]);
        $album->update(['cover_media_id' => $cover->id]);

        $parent->albums()->attach($album->id);

        $response = $this->actingAs($parent)->get(route('cabinet.index'));

        $response->assertOk();
        $response->assertSee(route('media.thumbnail', ['media' => $cover->id]));
        $response->assertDontSee('/storage/thumbnails/images/cover_thumb.webp', false);
    }

    // ── Pagination ─────────────────────────────────────────────────────

    public function test_album_with_many_photos_is_paginated(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);
        Photo::factory()->count(60)->create(['album_id' => $album->id]);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee('60 '.str('фото')->plural(60));
        $response->assertSee('page=2', false);
    }

    public function test_album_with_few_photos_has_no_pagination(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);
        Photo::factory()->count(3)->create(['album_id' => $album->id]);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertDontSee('page=2', false);
    }

    // ── Navigation ─────────────────────────────────────────────────────

    public function test_album_page_links_back_to_project(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);

        $response = $this->actingAs($client)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee(route('cabinet.project', $project));
    }

    public function test_parent_album_page_links_back_to_cabinet(): void
    {
        $parent = $this->userWithRole('parent');
        $album = Album::factory()->create(['type' => 'client', 'project_id' => null]);
        $parent->albums()->attach($album->id);

        $response = $this->actingAs($parent)->get(route('cabinet.album', $album));

        $response->assertOk();
        $response->assertSee(route('cabinet.index'));
    }

    // ── N+1 query check ────────────────────────────────────────────────

    public function test_album_page_has_no_n_plus_one_queries(): void
    {
        $client = $this->userWithRole('client');
        $album = $this->albumForClient($client, ['type' => 'client']);

        $media = Media::factory()->count(10)->create();
        foreach ($media as $i => $item) {
            Photo::factory()->create(['album_id' => $album->id, 'media_id' => $item->id]);
        }

        \DB::enableQueryLog();

        $this->actingAs($client)->get(route('cabinet.album', $album));

        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(10, $queryCount, "Expected ≤10 queries, got {$queryCount}. Possible N+1 issue.");
    }
}
