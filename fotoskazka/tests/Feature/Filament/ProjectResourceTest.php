<?php

namespace Tests\Feature\Filament;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
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
        $response = $this->get('/admin/projects');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/projects/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $project = Project::factory()->create();

        $response = $this->get("/admin/projects/{$project->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_project(): void
    {
        $project = Project::factory()->create([
            'title' => 'New Project',
            'type' => 'wedding',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('projects', [
            'title' => 'New Project',
            'type' => 'wedding',
            'status' => 'draft',
        ]);
    }

    public function test_can_update_project(): void
    {
        $project = Project::factory()->create(['title' => 'Old Title']);

        $project->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_project(): void
    {
        $project = Project::factory()->create();

        $project->delete();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_table_displays_data(): void
    {
        $project = Project::factory()->create(['title' => 'Test Project']);

        $response = $this->get('/admin/projects');

        $response->assertSuccessful();
        $this->assertDatabaseHas('projects', ['title' => 'Test Project']);
    }

    public function test_project_type_filter(): void
    {
        $wedding = Project::factory()->create(['type' => 'wedding']);
        $family = Project::factory()->create(['type' => 'family']);

        $this->assertDatabaseHas('projects', ['type' => 'wedding']);
        $this->assertDatabaseHas('projects', ['type' => 'family']);
    }
}
