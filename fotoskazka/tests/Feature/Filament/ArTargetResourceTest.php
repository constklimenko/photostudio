<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ArTargets\Pages\CreateArTarget;
use App\Filament\Resources\ArTargets\Pages\EditArTarget;
use App\Filament\Resources\ArTargets\Pages\ListArTargets;
use App\Filament\Resources\ArTargets\RelationManagers\ContentsRelationManager;
use App\Models\ArContent;
use App\Models\ArTarget;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ArTargetResourceTest extends TestCase
{
    use AdminTestCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signInAsAdmin();
        Storage::fake('public');
    }

    private function compiledUploads(): array
    {
        return [
            'marker_upload' => UploadedFile::fake()->image('marker.jpg', 1600, 1064),
            'mind_upload' => UploadedFile::fake()->createWithContent('photo.mind', str_repeat('m', 2048)),
            'marker_width' => 1600,
            'marker_height' => 1064,
            'feature_points' => 242,
        ];
    }

    private function setCompiled(Testable $component, array $data): void
    {
        foreach ($data as $key => $value) {
            $component->set("data.{$key}", $value);
        }
    }

    private function targetWithFiles(): ArTarget
    {
        $target = ArTarget::factory()->create(['disk' => 'public']);
        $target->update([
            'marker_path' => "ar/targets/{$target->id}/marker-old.jpg",
            'target_path' => "ar/targets/{$target->id}/target-old.mind",
        ]);
        Storage::disk('public')->put($target->marker_path, 'old');
        Storage::disk('public')->put($target->target_path, 'old');

        return $target;
    }

    public function test_pages_render(): void
    {
        $target = ArTarget::factory()->create(['disk' => 'public']);

        $this->get('/admin/ar-targets')->assertSuccessful();
        $this->get('/admin/ar-targets/create')->assertSuccessful();
        $this->get("/admin/ar-targets/{$target->id}/edit")->assertSuccessful();
    }

    public function test_photographer_without_panel_access_cannot_open_list(): void
    {
        $photographer = User::factory()->create(['status' => 'active']);
        $photographer->roles()->attach(Role::where('slug', 'photographer')->first());

        $this->actingAs($photographer)->get('/admin/ar-targets')->assertForbidden();
    }

    public function test_create_stores_compiled_files(): void
    {
        $project = Project::factory()->create();

        $component = Livewire::test(CreateArTarget::class)
            ->set('data.title', '11А, общее фото')
            ->set('data.project_id', $project->id);
        $this->setCompiled($component, $this->compiledUploads());
        $component->call('create')->assertHasNoErrors();

        $target = ArTarget::query()->where('title', '11А, общее фото')->firstOrFail();

        $this->assertSame($project->id, $target->project_id);
        $this->assertSame($this->admin->id, $target->created_by);
        $this->assertSame(1600, $target->marker_width);
        $this->assertSame(1064, $target->marker_height);
        $this->assertSame(242, $target->feature_points);
        $this->assertSame('mindar', $target->tracker);
        $this->assertSame('1.2.5', $target->tracker_version);
        $this->assertNotNull($target->compiled_at);
        $this->assertStringStartsWith("ar/targets/{$target->id}/marker-", $target->marker_path);
        $this->assertStringEndsWith('.jpg', $target->marker_path);
        $this->assertStringStartsWith("ar/targets/{$target->id}/target-", $target->target_path);
        $this->assertStringEndsWith('.mind', $target->target_path);
        Storage::disk('public')->assertExists($target->marker_path);
        Storage::disk('public')->assertExists($target->target_path);
    }

    public function test_create_requires_compiled_target(): void
    {
        Livewire::test(CreateArTarget::class)
            ->set('data.title', 'Без таргета')
            ->call('create')
            ->assertHasErrors(['data.mind_upload']);

        $this->assertDatabaseMissing('ar_targets', ['title' => 'Без таргета']);
    }

    public function test_create_rejects_non_mind_target_file(): void
    {
        $data = $this->compiledUploads();
        $data['mind_upload'] = UploadedFile::fake()->createWithContent('photo.txt', 'x');

        $component = Livewire::test(CreateArTarget::class)->set('data.title', 'Плохой файл');
        $this->setCompiled($component, $data);
        $component->call('create')->assertHasErrors(['data.mind_upload']);

        $this->assertDatabaseMissing('ar_targets', ['title' => 'Плохой файл']);
    }

    public function test_edit_without_new_photo_keeps_files_and_token(): void
    {
        $target = $this->targetWithFiles();
        $token = $target->token;

        Livewire::test(EditArTarget::class, ['record' => $target->getRouteKey()])
            ->set('data.title', 'Новое название')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('Новое название', $target->title);
        $this->assertSame($token, $target->token);
        Storage::disk('public')->assertExists("ar/targets/{$target->id}/marker-old.jpg");
    }

    public function test_edit_with_new_photo_replaces_files_and_keeps_token(): void
    {
        $target = $this->targetWithFiles();
        $token = $target->token;

        $component = Livewire::test(EditArTarget::class, ['record' => $target->getRouteKey()]);
        $this->setCompiled($component, $this->compiledUploads());
        $component->call('save')->assertHasNoErrors();

        $target->refresh();
        $this->assertSame($token, $target->token);
        $this->assertStringNotContainsString('old', $target->marker_path);
        Storage::disk('public')->assertExists($target->marker_path);
        Storage::disk('public')->assertExists($target->target_path);
        Storage::disk('public')->assertMissing("ar/targets/{$target->id}/marker-old.jpg");
        Storage::disk('public')->assertMissing("ar/targets/{$target->id}/target-old.mind");
    }

    public function test_regenerate_token_action(): void
    {
        $target = ArTarget::factory()->create(['disk' => 'public']);
        $token = $target->token;

        Livewire::test(EditArTarget::class, ['record' => $target->getRouteKey()])
            ->callAction('regenerateToken');

        $this->assertNotSame($token, $target->fresh()->token);
    }

    public function test_list_shows_targets_and_filters_by_activity(): void
    {
        $active = ArTarget::factory()->create(['title' => 'Активный']);
        $inactive = ArTarget::factory()->create(['title' => 'Выключенный', 'is_active' => false]);

        Livewire::test(ListArTargets::class)
            ->assertCanSeeTableRecords([$active, $inactive])
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_bulk_deactivate(): void
    {
        $targets = ArTarget::factory()->count(2)->create();

        Livewire::test(ListArTargets::class)
            ->callTableBulkAction('deactivate', $targets);

        $this->assertFalse($targets[0]->fresh()->is_active);
        $this->assertFalse($targets[1]->fresh()->is_active);
    }

    public function test_relation_manager_uploads_video(): void
    {
        $target = ArTarget::factory()->create(['disk' => 'public']);
        $path = UploadedFile::fake()->create('clip.mp4', 512, 'video/mp4')
            ->store("ar/targets/{$target->id}/contents", 'public');

        Livewire::test(ContentsRelationManager::class, [
            'ownerRecord' => $target,
            'pageClass' => EditArTarget::class,
        ])
            ->callTableAction('create', data: [
                'file_path' => [$path],
                'has_sound' => true,
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->assertHasNoTableActionErrors();

        $content = $target->contents()->firstOrFail();
        $this->assertSame('public', $content->disk);
        $this->assertSame($path, $content->file_path);
        $this->assertSame('video/mp4', $content->mime_type);
        $this->assertSame(Storage::disk('public')->size($path), $content->file_size);
    }

    public function test_relation_manager_delete_removes_file(): void
    {
        $target = ArTarget::factory()->create(['disk' => 'public']);
        Storage::disk('public')->put("ar/targets/{$target->id}/contents/a.mp4", 'v');
        $content = ArContent::factory()->create([
            'ar_target_id' => $target->id,
            'disk' => 'public',
            'file_path' => "ar/targets/{$target->id}/contents/a.mp4",
        ]);

        Livewire::test(ContentsRelationManager::class, [
            'ownerRecord' => $target,
            'pageClass' => EditArTarget::class,
        ])
            ->callTableAction('delete', $content);

        $this->assertDatabaseMissing('ar_contents', ['id' => $content->id]);
        Storage::disk('public')->assertMissing("ar/targets/{$target->id}/contents/a.mp4");
    }
}
