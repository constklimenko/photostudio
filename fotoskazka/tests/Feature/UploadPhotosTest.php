<?php

namespace Tests\Feature;

use App\Actions\Album\CreateAlbum;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
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

        $this->seed(RoleSeeder::class);

        Storage::fake('public');

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($this->admin);
    }

    public function test_upload_page_can_be_rendered(): void
    {
        $response = $this->get('/admin/albums/upload');

        $response->assertStatus(200);
    }

    public function test_create_album_with_photos(): void
    {
        $cover = UploadedFile::fake()->image('cover.jpg', 800, 600);
        $photo1 = UploadedFile::fake()->image('photo1.jpg', 1920, 1080);
        $photo2 = UploadedFile::fake()->image('photo2.jpg', 800, 1200);

        $coverPath = $cover->store('temp', 'public');
        $photoPaths = [
            $photo1->store('temp', 'public'),
            $photo2->store('temp', 'public'),
        ];

        $action = app(CreateAlbum::class);
        $album = $action->execute([
            'title' => 'Test Album',
            'type' => 'portfolio',
            'description' => 'Album description',
            'cover' => $coverPath,
            'photos' => $photoPaths,
        ]);

        $this->assertEquals('Test Album', $album->title);
        $this->assertEquals('portfolio', $album->type);
        $this->assertNotNull($album->cover_media_id);
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

    public function test_upload_without_cover(): void
    {
        $photo = UploadedFile::fake()->image('p.jpg', 100, 100);

        $action = app(CreateAlbum::class);
        $album = $action->execute([
            'title' => 'No Cover Album',
            'type' => 'client',
            'photos' => [$photo->store('temp', 'public')],
        ]);

        $this->assertNull($album->cover_media_id);
        $this->assertEquals('client', $album->type);
    }

    public function test_project_album_gets_project_id(): void
    {
        $project = Project::factory()->create();
        $photo = UploadedFile::fake()->image('p.jpg', 100, 100);

        $action = app(CreateAlbum::class);
        $album = $action->execute([
            'title' => 'Project Album',
            'type' => 'project',
            'project_id' => $project->id,
            'photos' => [$photo->store('temp', 'public')],
        ]);

        $this->assertEquals($project->id, $album->project_id);
        $this->assertInstanceOf(Project::class, $album->project);
    }

    public function test_non_project_album_has_null_project_id(): void
    {
        $photo = UploadedFile::fake()->image('p.jpg', 100, 100);

        $action = app(CreateAlbum::class);
        $album = $action->execute([
            'title' => 'Client Gallery',
            'type' => 'client',
            'photos' => [$photo->store('temp', 'public')],
        ]);

        $this->assertNull($album->project_id);
    }
}
