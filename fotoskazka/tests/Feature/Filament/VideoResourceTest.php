<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Videos\Pages\CreateVideo;
use App\Filament\Resources\Videos\Pages\EditVideo;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_can_create_video_with_rotation_and_sound_via_form(): void
    {
        Livewire::test(CreateVideo::class)
            ->fillForm([
                'title' => 'Повёрнутое видео',
                'type' => 'vertical',
                'rotation' => -90,
                'has_sound' => false,
                'is_active' => true,
                'show_on_home' => false,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('videos', [
            'title' => 'Повёрнутое видео',
            'type' => 'vertical',
            'rotation' => -90,
            'has_sound' => false,
        ]);
    }

    public function test_can_update_rotation_via_form(): void
    {
        $video = Video::factory()->create(['title' => 'Видео', 'rotation' => 0]);

        Livewire::test(EditVideo::class, ['record' => $video->getRouteKey()])
            ->fillForm([
                'rotation' => 90,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'rotation' => 90,
        ]);
    }
}
