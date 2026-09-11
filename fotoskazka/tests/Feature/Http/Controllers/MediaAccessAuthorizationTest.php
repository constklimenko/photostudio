<?php

namespace Tests\Feature\Http\Controllers;

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

class MediaAccessAuthorizationTest extends TestCase
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

    private function privateMediaFor(Project $project, ?string $content = null): Media
    {
        $media = $this->createMedia($content);

        $album = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'is_published' => true,
        ]);

        Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
        ]);

        return $media;
    }

    private function publicMedia(?string $content = null): Media
    {
        $media = $this->createMedia($content);

        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
        ]);

        Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
        ]);

        return $media;
    }

    private function mediaWithThumbnail(?string $content = null): Media
    {
        Storage::disk('thumbnails')->put('images/photo_thumb.webp', $content ?? 'binary-webp-thumbnail');

        return Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'images/photo.jpg',
            'thumbnail_path' => 'images/photo_thumb.webp',
            'mime_type' => 'image/webp',
        ]);
    }

    private function privateThumbnailMediaFor(Project $project): Media
    {
        $media = $this->mediaWithThumbnail();

        $album = Album::factory()->create([
            'project_id' => $project->id,
            'type' => 'client',
            'is_published' => true,
        ]);

        Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
        ]);

        return $media;
    }

    public function test_public_media_is_served_to_guest(): void
    {
        $media = $this->publicMedia();

        $this->get(route('media.original', ['media' => $media]))
            ->assertSuccessful();
    }

    public function test_private_media_is_hidden_from_guest(): void
    {
        $project = Project::factory()->create();
        $media = $this->privateMediaFor($project);

        $this->get(route('media.original', ['media' => $media]))
            ->assertNotFound();
    }

    public function test_own_client_can_view_media_of_its_private_album(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $media = $this->privateMediaFor($project);

        $this->actingAs($client)
            ->get(route('media.original', ['media' => $media]))
            ->assertSuccessful();
    }

    public function test_own_class_manager_can_view_media_of_own_client_album(): void
    {
        $manager = $this->userWithRole('class_manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $media = $this->privateMediaFor($project);

        $this->actingAs($manager)
            ->get(route('media.original', ['media' => $media]))
            ->assertSuccessful();
    }

    public function test_client_cannot_view_media_of_alien_project(): void
    {
        $project = Project::factory()->create(['client_id' => $this->userWithRole('client')->id]);
        $media = $this->privateMediaFor($project);

        $stranger = $this->userWithRole('client');

        $this->actingAs($stranger)
            ->get(route('media.original', ['media' => $media]))
            ->assertForbidden();
    }

    public function test_private_media_thumbnail_is_hidden_from_guest(): void
    {
        $project = Project::factory()->create();
        $media = $this->privateThumbnailMediaFor($project);

        $this->get(route('media.thumbnail', ['media' => $media]))
            ->assertNotFound();
    }

    public function test_private_media_thumbnail_is_forbidden_for_foreign_client(): void
    {
        $project = Project::factory()->create(['client_id' => $this->userWithRole('client')->id]);
        $media = $this->privateThumbnailMediaFor($project);

        $stranger = $this->userWithRole('client');

        $this->actingAs($stranger)
            ->get(route('media.thumbnail', ['media' => $media]))
            ->assertForbidden();
    }

    public function test_own_client_can_view_thumbnail_of_private_album(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $media = $this->privateThumbnailMediaFor($project);

        $this->actingAs($client)
            ->get(route('media.thumbnail', ['media' => $media]))
            ->assertSuccessful();
    }

    public function test_public_media_thumbnail_is_served_to_guest(): void
    {
        $media = $this->mediaWithThumbnail('webp-bytes');

        $this->get(route('media.thumbnail', ['media' => $media]))
            ->assertSuccessful();
    }

    public function test_missing_thumbnail_returns_404_for_authorized_user(): void
    {
        $client = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $client->id]);
        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'images/photo.jpg',
            'thumbnail_path' => 'images/does_not_exist_thumb.webp',
            'mime_type' => 'image/webp',
        ]);

        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client']);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);

        $this->actingAs($client)
            ->get(route('media.thumbnail', ['media' => $media]))
            ->assertNotFound();
    }

    public function test_foreign_client_cannot_view_media_of_alien_private_album(): void
    {
        $owner = $this->userWithRole('client');
        $project = Project::factory()->create(['client_id' => $owner->id]);
        $media = $this->privateMediaFor($project);

        $stranger = $this->userWithRole('client');

        $this->actingAs($stranger)
            ->get(route('media.original', ['media' => $media]))
            ->assertForbidden();
    }

    public function test_foreign_parent_cannot_view_media_of_private_album(): void
    {
        $project = Project::factory()->create();
        $media = $this->privateMediaFor($project);

        $parent = $this->userWithRole('parent');

        $this->actingAs($parent)
            ->get(route('media.original', ['media' => $media]))
            ->assertForbidden();
    }

    public function test_assigned_parent_can_view_media_of_own_client_album(): void
    {
        $parent = $this->userWithRole('parent');
        $project = Project::factory()->create();
        $media = $this->createMedia();
        $album = Album::factory()->create(['project_id' => $project->id, 'type' => 'client', 'is_published' => true]);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);
        $parent->albums()->attach($album->id);

        $this->actingAs($parent)
            ->get(route('media.original', ['media' => $media]))
            ->assertSuccessful();
    }

    public function test_foreign_class_manager_cannot_view_media_of_private_album(): void
    {
        $project = Project::factory()->create();
        $media = $this->privateMediaFor($project);

        $manager = $this->userWithRole('class_manager');

        $this->actingAs($manager)
            ->get(route('media.original', ['media' => $media]))
            ->assertForbidden();
    }

    public function test_admin_can_view_any_media(): void
    {
        $project = Project::factory()->create();
        $media = $this->privateMediaFor($project);

        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('media.original', ['media' => $media]))
            ->assertSuccessful();
    }

    public function test_private_media_display_is_denied_to_guest(): void
    {
        $project = Project::factory()->create();
        $media = $this->privateMediaFor($project, $this->makeJpegBytes(800, 600));

        $this->get(route('media.display', ['media' => $media]))
            ->assertNotFound();
    }

    public function test_private_media_lightbox_is_denied_to_foreign_user(): void
    {
        $project = Project::factory()->create();
        $media = $this->privateMediaFor($project, $this->makeJpegBytes(800, 600));

        $stranger = $this->userWithRole('client');

        $this->actingAs($stranger)
            ->get(route('media.lightbox', ['media' => $media]))
            ->assertForbidden();
    }

    public function test_public_media_display_is_served_to_guest(): void
    {
        $media = $this->publicMedia($this->makeJpegBytes(800, 600));

        $this->get(route('media.display', ['media' => $media]))
            ->assertSuccessful();
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
