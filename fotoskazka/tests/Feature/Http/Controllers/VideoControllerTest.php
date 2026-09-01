<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_successful_response(): void
    {
        $response = $this->get(route('video.index'));

        $response->assertStatus(200);
    }

    public function test_index_shows_page_title(): void
    {
        Page::factory()->create([
            'slug' => 'video',
            'title' => 'Видеогалерея',
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get(route('video.index'));

        $response->assertSee('Видеогалерея');
    }

    public function test_index_shows_horizontal_videos(): void
    {
        $video = Video::factory()->create([
            'type' => 'horizontal',
            'title' => 'Свадебный фильм',
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('Свадебный фильм');
        $response->assertSee($video->embed_url);
    }

    public function test_index_shows_vertical_videos(): void
    {
        Video::factory()->create([
            'type' => 'vertical',
            'title' => 'Вертикальное видео',
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('Вертикальное видео');
        $response->assertSee('Вертикальные видео');
    }

    public function test_index_splits_videos_by_orientation(): void
    {
        $horizontal = Video::factory()->create([
            'type' => 'horizontal',
            'title' => 'Горизонтальный ролик',
        ]);
        $vertical = Video::factory()->create([
            'type' => 'vertical',
            'title' => 'Вертикальный ролик',
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('Горизонтальный ролик');
        $response->assertSee('Вертикальный ролик');
        $response->assertSee($horizontal->embed_url);
        $response->assertSee($vertical->embed_url);
    }

    public function test_index_hides_inactive_videos(): void
    {
        Video::factory()->create([
            'is_active' => false,
            'title' => 'Скрытое видео',
        ]);

        $response = $this->get(route('video.index'));

        $response->assertDontSee('Скрытое видео');
    }

    public function test_index_shows_uploaded_video(): void
    {
        Storage::fake('public');
        Storage::fake('thumbnails');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee($video->source_url);
        $response->assertSee('video/mp4');
    }

    public function test_index_orders_videos_by_sort_order(): void
    {
        Video::factory()->create([
            'title' => 'Второе видео',
            'sort_order' => 10,
        ]);
        Video::factory()->create([
            'title' => 'Первое видео',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSeeInOrder(['Первое видео', 'Второе видео']);
    }

    public function test_index_shows_empty_state(): void
    {
        $response = $this->get(route('video.index'));

        $response->assertSee('Видео пока не добавлены');
    }

    public function test_index_uploaded_video_uses_proxy_route_not_public_url(): void
    {
        Storage::fake('public');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee(route('video.file', $video->id));
        $response->assertDontSee('/storage/videos/clip.mp4');
    }

    public function test_raw_file_route_returns_video_inline(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', 'fake-binary-content');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.file', $video->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Content-Disposition', 'inline; filename="clip.mp4"');
        $response->assertHeaderContains('Cache-Control', 'no-store');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('fake-binary-content', $response->streamedContent());
    }

    public function test_stream_route_returns_html_player_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', 'fake-binary-content');

        $video = Video::factory()->create([
            'title' => 'Видео плеер',
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id));

        $response->assertStatus(200);
        $response->assertSee('Видео плеер');
        $response->assertSee(route('video.file', $video->id));
        $response->assertSee('<video', false);
        $this->assertStringNotContainsString('fake-binary-content', $response->getContent());
    }

    public function test_stream_player_renders_rotation_when_enabled(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', 'fake-binary-content');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
            'type' => 'vertical',
            'rotate_90' => true,
        ]);

        $response = $this->get(route('video.stream', $video->id));

        $response->assertStatus(200);
        $response->assertSee('rotate(90deg)', false);
    }

    public function test_file_returns_404_when_video_has_no_file(): void
    {
        $video = Video::factory()->create([
            'file_path' => null,
            'url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        $response = $this->get(route('video.file', $video->id));

        $response->assertStatus(404);
    }

    public function test_stream_returns_404_when_video_has_no_file(): void
    {
        $video = Video::factory()->create([
            'file_path' => null,
            'url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        $response = $this->get(route('video.stream', $video->id));

        $response->assertStatus(404);
    }

    public function test_index_renders_rotation_and_download_protection_attributes(): void
    {
        Storage::fake('public');

        $video = Video::factory()->create([
            'title' => 'Повёрнутое видео',
            'type' => 'vertical',
            'file_path' => 'videos/clip.mp4',
            'url' => '',
            'rotate_90' => true,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('rotate(90deg)', false);
        $response->assertSee('controlsList', false);
        $response->assertSee('nodownload', false);
        $response->assertSee('disablepictureinpicture', false);
        $response->assertSee('oncontextmenu="return false"', false);
    }

    public function test_index_does_not_render_rotation_when_disabled(): void
    {
        Storage::fake('public');

        $video = Video::factory()->create([
            'title' => 'Обычное видео',
            'type' => 'vertical',
            'file_path' => 'videos/clip.mp4',
            'url' => '',
            'rotate_90' => false,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertDontSee('rotate(90deg)', false);
    }
}
