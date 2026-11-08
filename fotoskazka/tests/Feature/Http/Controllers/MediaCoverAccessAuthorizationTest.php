<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\MediaAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Регрессионные тесты: обложка приватного альбома (cover_media_id)
 * не должна отдаваться по /media/{id}/* без прав на соответствующий Album.
 *
 * Обложка создаётся отдельной записью Media и связана с альбомом только
 * через albums.cover_media_id (без строки photos), поэтому раньше выпадала
 * из цепочки Media → Photo → Album и была публичной.
 */
class MediaCoverAccessAuthorizationTest extends TestCase
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

    private function createMedia(?string $content = null): Media
    {
        Storage::disk('public')->put('images/photo.jpg', $content ?? 'binary-jpeg-content');

        return Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'images/photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    private function coverMediaFor(Album $album, ?Media $media = null): Media
    {
        $cover = $media ?? $this->createMedia();

        $album->update(['cover_media_id' => $cover->id]);

        return $cover->refresh();
    }

    private function privateAlbum(string $type, ?Project $project = null): Album
    {
        return Album::factory()->create([
            'type' => $type,
            'project_id' => $project?->id,
            'is_published' => true,
        ]);
    }

    public function test_cover_of_client_album_is_hidden_from_guest(): void
    {
        $album = $this->privateAlbum('client');
        $cover = $this->coverMediaFor($album);

        $this->get(route('media.original', ['media' => $cover]))
            ->assertNotFound();
    }

    public function test_cover_of_client_album_is_hidden_from_foreign_client(): void
    {
        $owner = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $owner->id]);
        $album = $this->privateAlbum('client', $project);
        $cover = $this->coverMediaFor($album);

        $stranger = $this->userWithRole('client');

        $this->actingAs($stranger)
            ->get(route('media.original', ['media' => $cover]))
            ->assertForbidden();
    }

    public function test_cover_of_client_album_is_visible_to_own_client(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $album = $this->privateAlbum('client', $project);
        $cover = $this->coverMediaFor($album);

        $this->actingAs($client)
            ->get(route('media.original', ['media' => $cover]))
            ->assertSuccessful();
    }

    public function test_cover_of_client_album_is_visible_to_own_class_manager(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $album = $this->privateAlbum('client', $project);
        $cover = $this->coverMediaFor($album);

        $this->actingAs($manager)
            ->get(route('media.original', ['media' => $cover]))
            ->assertSuccessful();
    }

    public function test_cover_of_client_album_is_hidden_from_foreign_class_manager(): void
    {
        $album = $this->privateAlbum('client');
        $cover = $this->coverMediaFor($album);

        $manager = $this->userWithRole('class_manager');

        $this->actingAs($manager)
            ->get(route('media.original', ['media' => $cover]))
            ->assertForbidden();
    }

    public function test_cover_of_client_album_is_visible_to_assigned_parent(): void
    {
        $parent = $this->userWithRole('parent');
        $album = $this->privateAlbum('client');
        $cover = $this->coverMediaFor($album);
        $parent->albums()->attach($album->id);

        $this->actingAs($parent)
            ->get(route('media.original', ['media' => $cover]))
            ->assertSuccessful();
    }

    public function test_cover_of_client_album_is_hidden_from_foreign_parent(): void
    {
        $album = $this->privateAlbum('client');
        $cover = $this->coverMediaFor($album);

        $parent = $this->userWithRole('parent');

        $this->actingAs($parent)
            ->get(route('media.original', ['media' => $cover]))
            ->assertForbidden();
    }

    public function test_cover_of_private_album_is_hidden_from_guest_on_thumbnail(): void
    {
        Storage::disk('thumbnails')->put('images/photo_thumb.webp', 'binary-webp-thumbnail');

        $cover = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'images/photo.jpg',
            'thumbnail_path' => 'images/photo_thumb.webp',
            'mime_type' => 'image/webp',
        ]);
        $album = $this->privateAlbum('client');
        $this->coverMediaFor($album, $cover);

        $this->get(route('media.thumbnail', ['media' => $cover]))
            ->assertNotFound();
    }

    public function test_cover_of_private_album_is_hidden_from_guest_on_display(): void
    {
        $cover = $this->createMedia($this->makeJpegBytes(800, 600));
        $album = $this->privateAlbum('client');
        $this->coverMediaFor($album, $cover);

        $this->get(route('media.display', ['media' => $cover]))
            ->assertNotFound();
    }

    public function test_cover_of_private_album_is_hidden_from_foreign_user_on_lightbox(): void
    {
        $cover = $this->createMedia($this->makeJpegBytes(800, 600));
        $album = $this->privateAlbum('client');
        $this->coverMediaFor($album, $cover);

        $stranger = $this->userWithRole('client');

        $this->actingAs($stranger)
            ->get(route('media.lightbox', ['media' => $cover]))
            ->assertForbidden();
    }

    public function test_cover_of_project_album_is_hidden_from_foreign_client(): void
    {
        $owner = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $owner->id]);
        $album = $this->privateAlbum('project', $project);
        $cover = $this->coverMediaFor($album);

        $stranger = $this->userWithRole('client');

        $this->actingAs($stranger)
            ->get(route('media.original', ['media' => $cover]))
            ->assertForbidden();
    }

    public function test_cover_of_project_album_is_visible_to_own_client(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $album = $this->privateAlbum('project', $project);
        $cover = $this->coverMediaFor($album);

        $this->actingAs($client)
            ->get(route('media.original', ['media' => $cover]))
            ->assertSuccessful();
    }

    public function test_admin_can_view_cover_of_private_album(): void
    {
        $album = $this->privateAlbum('client');
        $cover = $this->coverMediaFor($album);

        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('media.original', ['media' => $cover]))
            ->assertSuccessful();
    }

    public function test_cover_of_public_album_stays_public(): void
    {
        $album = Album::factory()->create(['type' => 'portfolio', 'is_published' => true]);
        $cover = $this->coverMediaFor($album);

        $this->get(route('media.original', ['media' => $cover]))
            ->assertSuccessful();
    }

    public function test_photo_linked_to_private_album_stays_private(): void
    {
        $album = $this->privateAlbum('client');
        $media = $this->createMedia();

        Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
        ]);

        $this->assertFalse(app(MediaAccessService::class)->isPublic($media));

        $this->get(route('media.original', ['media' => $media]))
            ->assertNotFound();
    }

    public function test_is_public_returns_false_for_cover_of_private_album(): void
    {
        $album = $this->privateAlbum('client');
        $cover = $this->coverMediaFor($album);

        $this->assertFalse(app(MediaAccessService::class)->isPublic($cover));
    }

    public function test_is_public_returns_true_for_cover_of_public_album(): void
    {
        $album = Album::factory()->create(['type' => 'portfolio', 'is_published' => true]);
        $cover = $this->coverMediaFor($album);

        $this->assertTrue(app(MediaAccessService::class)->isPublic($cover));
    }

    private function makeJpegBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 90, 30, 180));

        ob_start();
        imagejpeg($image, quality: 85);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
