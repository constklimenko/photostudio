<?php

namespace Tests\Feature\Models;

use App\Models\ArContent;
use App\Models\ArTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_target(): void
    {
        $target = ArTarget::factory()->create();
        $content = ArContent::factory()->create(['ar_target_id' => $target->id]);

        $this->assertTrue($content->target->is($target));
    }

    public function test_defaults(): void
    {
        $content = ArContent::factory()->create();
        $fresh = $content->fresh();

        $this->assertSame('video', $fresh->type);
        $this->assertTrue($fresh->has_sound);
        $this->assertTrue($fresh->is_active);
        $this->assertSame(0, $fresh->sort_order);
    }

    public function test_disk_defaults_to_configured_ar_disk(): void
    {
        Config::set('filesystems.ar_disk', 's3');

        $content = ArContent::factory()->create(['disk' => null]);

        $this->assertSame('s3', $content->disk);
    }

    public function test_deleting_content_removes_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ar/targets/1/contents/5.mp4', 'v');

        $content = ArContent::factory()->create([
            'disk' => 'public',
            'file_path' => 'ar/targets/1/contents/5.mp4',
        ]);

        $content->delete();

        Storage::disk('public')->assertMissing('ar/targets/1/contents/5.mp4');
    }
}
