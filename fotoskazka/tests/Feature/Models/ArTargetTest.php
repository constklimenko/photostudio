<?php

namespace Tests\Feature\Models;

use App\Models\Album;
use App\Models\ArContent;
use App\Models\ArTarget;
use App\Models\Photo;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_is_generated_on_create(): void
    {
        $target = ArTarget::factory()->create();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{12}$/', $target->token);
    }

    public function test_tokens_are_unique(): void
    {
        $tokens = ArTarget::factory()->count(20)->create()->pluck('token');

        $this->assertCount(20, $tokens->unique());
    }

    public function test_token_is_not_mass_assignable(): void
    {
        $target = (new ArTarget)->fill(['token' => 'forcedtoken1']);

        $this->assertNull($target->token);
    }

    public function test_token_does_not_change_on_update(): void
    {
        $target = ArTarget::factory()->create();
        $token = $target->token;

        $target->update(['title' => 'Новое название', 'target_path' => 'ar/targets/1/target.mind']);

        $this->assertSame($token, $target->fresh()->token);
    }

    public function test_regenerate_token_replaces_token(): void
    {
        $target = ArTarget::factory()->create();
        $old = $target->token;

        $target->regenerateToken();

        $this->assertNotSame($old, $target->fresh()->token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{12}$/', $target->fresh()->token);
    }

    public function test_disk_defaults_to_configured_ar_disk(): void
    {
        Config::set('filesystems.ar_disk', 's3');

        $target = ArTarget::factory()->create(['disk' => null]);

        $this->assertSame('s3', $target->disk);
    }

    public function test_defaults(): void
    {
        $target = ArTarget::factory()->create();

        $this->assertTrue($target->fresh()->is_active);
        $this->assertSame('mindar', $target->fresh()->tracker);
    }

    public function test_marker_aspect_is_height_to_width(): void
    {
        $target = ArTarget::factory()->make(['marker_width' => 1600, 'marker_height' => 1064]);

        $this->assertEqualsWithDelta(0.665, $target->markerAspect(), 0.001);
    }

    public function test_belongs_to_project_photo_and_creator(): void
    {
        $project = Project::factory()->create();
        $photo = Photo::factory()->create();
        $user = User::factory()->create();

        $target = ArTarget::factory()->create([
            'project_id' => $project->id,
            'photo_id' => $photo->id,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($target->project->is($project));
        $this->assertTrue($target->photo->is($photo));
        $this->assertTrue($target->creator->is($user));
    }

    public function test_deleting_photo_keeps_target(): void
    {
        $photo = Photo::factory()->create();
        $target = ArTarget::factory()->create(['photo_id' => $photo->id]);

        $photo->delete();

        $this->assertNull($target->fresh()->photo_id);
        $this->assertSame($target->token, $target->fresh()->token);
    }

    public function test_deleting_album_keeps_target_and_contents(): void
    {
        $album = Album::factory()->create(['type' => 'client']);
        $photo = Photo::factory()->create(['album_id' => $album->id]);
        $target = ArTarget::factory()->create(['photo_id' => $photo->id]);
        ArContent::factory()->create(['ar_target_id' => $target->id]);

        $album->delete();

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
        $this->assertNull($target->fresh()->photo_id);
        $this->assertCount(1, $target->fresh()->contents);
    }

    public function test_deleting_project_keeps_target(): void
    {
        $project = Project::factory()->create();
        $target = ArTarget::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertNull($target->fresh()->project_id);
    }

    public function test_contents_are_ordered_and_active_contents_filtered(): void
    {
        $target = ArTarget::factory()->create();
        $second = ArContent::factory()->create(['ar_target_id' => $target->id, 'sort_order' => 2]);
        $first = ArContent::factory()->create(['ar_target_id' => $target->id, 'sort_order' => 1]);
        ArContent::factory()->create(['ar_target_id' => $target->id, 'sort_order' => 0, 'is_active' => false]);

        $this->assertSame([$first->id, $second->id], $target->activeContents()->pluck('id')->all());
        $this->assertCount(3, $target->contents);
    }

    public function test_deleting_target_removes_contents_and_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ar/targets/1/marker.jpg', 'm');
        Storage::disk('public')->put('ar/targets/1/target.mind', 't');
        Storage::disk('public')->put('ar/targets/1/contents/1.mp4', 'v');

        $target = ArTarget::factory()->create([
            'disk' => 'public',
            'marker_path' => 'ar/targets/1/marker.jpg',
            'target_path' => 'ar/targets/1/target.mind',
        ]);
        $content = ArContent::factory()->create([
            'ar_target_id' => $target->id,
            'disk' => 'public',
            'file_path' => 'ar/targets/1/contents/1.mp4',
        ]);

        $target->delete();

        $this->assertDatabaseMissing('ar_contents', ['id' => $content->id]);
        Storage::disk('public')->assertMissing('ar/targets/1/marker.jpg');
        Storage::disk('public')->assertMissing('ar/targets/1/target.mind');
        Storage::disk('public')->assertMissing('ar/targets/1/contents/1.mp4');
    }
}
