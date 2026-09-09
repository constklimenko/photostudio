<?php

namespace Tests\Feature\Models;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_cast_to_enum(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Processing]);

        $this->assertInstanceOf(ProjectStatus::class, $project->status);
        $this->assertSame(ProjectStatus::Processing, $project->status);
    }

    public function test_status_defaults_to_draft(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Draft]);

        $this->assertEquals(ProjectStatus::Draft, $project->status);
        $this->assertEquals('draft', Project::find($project->id)->getRawOriginal('status'));
    }

    public function test_status_returns_raw_string_in_database(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Completed]);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'completed']);
    }

    public function test_every_status_is_supported(): void
    {
        $expected = ['draft', 'shooting_completed', 'reshoot', 'processing', 'layout_approval', 'printing', 'completed', 'archived'];

        foreach ($expected as $value) {
            $project = Project::factory()->create(['status' => ProjectStatus::from($value)]);
            $this->assertSame($value, Project::find($project->id)->getRawOriginal('status'));
        }
    }
}
