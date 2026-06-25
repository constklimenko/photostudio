<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadPhotosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    public function test_upload_page_can_be_rendered(): void
    {
        $response = $this->get('/admin/albums/upload');

        $response->assertStatus(200);
    }

    public function test_upload_creates_album_media_and_photos(): void
    {
        $cover = UploadedFile::fake()->image('cover.jpg', 800, 600);
        $photo1 = UploadedFile::fake()->image('photo1.jpg', 1920, 1080);
        $photo2 = UploadedFile::fake()->image('photo2.jpg', 800, 1200);

        $coverPath = $cover->store('temp', 'public');
        $photoPaths = [
            $photo1->store('temp', 'public'),
            $photo2->store('temp', 'public'),
        ];

        $album = Album::create([
            'title' => 'Test Album',
            'slug' => 'test-album-20260625120000',
            'description' => 'Album description',
            'type' => 'portfolio',
        ]);

        if ($coverPath) {
            $coverMedia = Media::create([
                'file_path' => $coverPath,
                'disk' => 'public',
                'collection' => 'covers',
                'title' => $album->title,
            ]);
            $album->update(['cover_media_id' => $coverMedia->id]);
        }

        foreach ($photoPaths as $index => $photoPath) {
            $media = Media::create([
                'file_path' => $photoPath,
                'disk' => 'public',
                'collection' => 'gallery',
                'title' => $album->title.' — '.($index + 1),
            ]);

            Photo::create([
                'album_id' => $album->id,
                'media_id' => $media->id,
                'sort_order' => $index,
            ]);
        }

        $this->assertDatabaseHas('albums', [
            'id' => $album->id,
            'title' => 'Test Album',
            'type' => 'portfolio',
            'cover_media_id' => $coverMedia->id ?? null,
        ]);

        $this->assertEquals(2, $album->photos()->count());

        foreach ($album->photos as $i => $photo) {
            $this->assertEquals($i, $photo->sort_order);
            $this->assertNotNull($photo->media);
            $this->assertEquals('gallery', $photo->media->collection);
        }

        Storage::disk('public')->assertExists($coverPath);
        Storage::disk('public')->assertExists($photoPaths[0]);
        Storage::disk('public')->assertExists($photoPaths[1]);
    }

    public function test_upload_without_cover_works(): void
    {
        $album = Album::create([
            'title' => 'No Cover Album',
            'slug' => 'no-cover-album',
            'type' => 'client',
        ]);

        $this->assertNull($album->cover_media_id);
        $this->assertEquals('client', $album->type);
    }

    public function test_project_album_can_have_project_id(): void
    {
        $project = Project::factory()->create();

        $album = Album::create([
            'title' => 'Project Album',
            'slug' => 'project-album',
            'type' => 'project',
            'project_id' => $project->id,
        ]);

        $this->assertEquals($project->id, $album->project_id);
        $this->assertInstanceOf(Project::class, $album->project);
    }

    public function test_non_project_album_has_null_project_id(): void
    {
        $album = Album::create([
            'title' => 'Client Gallery',
            'slug' => 'client-gallery',
            'type' => 'client',
        ]);

        $this->assertNull($album->project_id);
    }
}
