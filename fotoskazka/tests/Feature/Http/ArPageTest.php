<?php

namespace Tests\Feature\Http;

use App\Models\ArContent;
use App\Models\ArTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function readyTarget(array $content = []): ArTarget
    {
        $target = ArTarget::factory()->create([
            'disk' => 'public',
            'title' => '11А, общее фото',
            'marker_width' => 1600,
            'marker_height' => 1064,
        ]);
        $target->update([
            'marker_path' => "ar/targets/{$target->id}/marker-a.jpg",
            'target_path' => "ar/targets/{$target->id}/target-a.mind",
        ]);
        ArContent::factory()->create([
            'ar_target_id' => $target->id,
            'disk' => 'public',
            'file_path' => "ar/targets/{$target->id}/contents/clip.mp4",
            ...$content,
        ]);

        return $target;
    }

    private function config($response): array
    {
        preg_match('/<script type="application\/json" id="ar-config">(.*?)<\/script>/s', $response->getContent(), $m);
        $this->assertNotEmpty($m, 'ar-config не найден');

        return json_decode(html_entity_decode($m[1]), true);
    }

    public function test_active_target_renders_viewer_with_config(): void
    {
        $target = $this->readyTarget(['has_sound' => true]);

        $response = $this->get("/ar/{$target->token}");

        $response->assertOk()->assertSee('Старт');
        $config = $this->config($response);
        $this->assertSame(Storage::disk('public')->url($target->target_path), $config['target']);
        $this->assertSame(Storage::disk('public')->url("ar/targets/{$target->id}/contents/clip.mp4"), $config['video']);
        $this->assertEqualsWithDelta(1064 / 1600, $config['aspect'], 0.0001);
        $this->assertTrue($config['hasSound']);
    }

    public function test_video_without_sound_is_flagged(): void
    {
        $target = $this->readyTarget(['has_sound' => false]);

        $this->assertFalse($this->config($this->get("/ar/{$target->token}"))['hasSound']);
    }

    public function test_first_active_content_is_used(): void
    {
        $target = $this->readyTarget(['sort_order' => 5]);
        ArContent::factory()->create([
            'ar_target_id' => $target->id,
            'disk' => 'public',
            'file_path' => 'ar/first.mp4',
            'sort_order' => 1,
        ]);
        ArContent::factory()->create([
            'ar_target_id' => $target->id,
            'disk' => 'public',
            'file_path' => 'ar/hidden.mp4',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $config = $this->config($this->get("/ar/{$target->token}"));

        $this->assertSame(Storage::disk('public')->url('ar/first.mp4'), $config['video']);
    }

    public function test_page_is_not_indexed(): void
    {
        $target = $this->readyTarget();

        $this->get("/ar/{$target->token}")
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_unknown_token_is_404(): void
    {
        $this->get('/ar/doesnotexist')->assertNotFound();
    }

    public function test_inactive_target_is_410(): void
    {
        $target = $this->readyTarget();
        $target->update(['is_active' => false]);

        $this->get("/ar/{$target->token}")
            ->assertStatus(410)
            ->assertSee('Ссылка отключена')
            ->assertDontSee('ar-config');
    }

    public function test_target_without_active_video_shows_not_ready(): void
    {
        $target = $this->readyTarget(['is_active' => false]);

        $this->get("/ar/{$target->token}")
            ->assertOk()
            ->assertSee('Видео ещё не загружено')
            ->assertDontSee('ar-config');
    }

    public function test_target_without_compiled_file_shows_not_ready(): void
    {
        $target = $this->readyTarget();
        $target->update(['target_path' => null]);

        $this->get("/ar/{$target->token}")
            ->assertOk()
            ->assertSee('Видео ещё не загружено');
    }

    public function test_route_is_throttled(): void
    {
        $target = $this->readyTarget();

        for ($i = 0; $i < 60; $i++) {
            $this->get("/ar/{$target->token}")->assertOk();
        }

        $this->get("/ar/{$target->token}")->assertStatus(429);
    }
}
