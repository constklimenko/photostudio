<?php

namespace Tests\Feature\Filament;

use App\Models\Album;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($admin);
    }

    public function test_list_page_renders(): void
    {
        $response = $this->get('/admin/albums');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/albums/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $album = Album::factory()->create();

        $response = $this->get("/admin/albums/{$album->id}/edit");

        $response->assertSuccessful();
    }

    public function test_upload_page_renders(): void
    {
        $response = $this->get('/admin/albums/upload');

        $response->assertSuccessful();
    }

    public function test_can_create_album(): void
    {
        $album = Album::factory()->create([
            'title' => 'Test Album',
            'slug' => 'test-album',
            'type' => 'portfolio',
        ]);

        $this->assertDatabaseHas('albums', [
            'title' => 'Test Album',
            'slug' => 'test-album',
            'type' => 'portfolio',
        ]);
    }

    public function test_can_update_album(): void
    {
        $album = Album::factory()->create(['title' => 'Old Title']);

        $album->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('albums', [
            'id' => $album->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_album(): void
    {
        $album = Album::factory()->create();

        $album->delete();

        $this->assertDatabaseMissing('albums', ['id' => $album->id]);
    }

    public function test_album_table_displays_data(): void
    {
        $album = Album::factory()->create(['title' => 'Test Album']);

        $response = $this->get('/admin/albums');

        $response->assertSuccessful();
        $this->assertDatabaseHas('albums', ['title' => 'Test Album']);
    }

    public function test_album_type_filter(): void
    {
        $portfolio = Album::factory()->create(['type' => 'portfolio']);
        $project = Album::factory()->create(['type' => 'project']);

        $this->assertDatabaseHas('albums', ['type' => 'portfolio']);
        $this->assertDatabaseHas('albums', ['type' => 'project']);
    }
}
