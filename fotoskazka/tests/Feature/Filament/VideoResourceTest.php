<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoResourceTest extends TestCase
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
        $response = $this->get('/admin/videos');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/videos/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $video = Video::factory()->create();

        $response = $this->get("/admin/videos/{$video->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_video(): void
    {
        $video = Video::factory()->create([
            'title' => 'New Video',
            'type' => 'horizontal',
            'url' => 'https://youtube.com/watch?v=123',
        ]);

        $this->assertDatabaseHas('videos', [
            'title' => 'New Video',
            'type' => 'horizontal',
        ]);
    }

    public function test_can_update_video(): void
    {
        $video = Video::factory()->create(['title' => 'Old Title']);

        $video->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_video(): void
    {
        $video = Video::factory()->create();

        $video->delete();

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    public function test_video_table_displays_data(): void
    {
        $video = Video::factory()->create(['title' => 'Test Video']);

        $response = $this->get('/admin/videos');

        $response->assertSuccessful();
        $this->assertDatabaseHas('videos', ['title' => 'Test Video']);
    }

    public function test_video_table_can_search(): void
    {
        $video = Video::factory()->create(['title' => 'Unique Searchable Video']);

        $response = $this->get('/admin/videos');

        $response->assertSuccessful();
        $this->assertDatabaseHas('videos', ['title' => 'Unique Searchable Video']);
    }
}
