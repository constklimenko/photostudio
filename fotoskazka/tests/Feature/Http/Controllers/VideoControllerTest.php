<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use App\Models\Video;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use Mockery;
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

        $response->assertSee(route('video.stream', $video->id));
        $response->assertDontSee('/storage/videos/clip.mp4');
    }

    public function test_stream_route_returns_video_inline(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', 'fake-binary-content');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Content-Disposition', 'inline; filename="clip.mp4"');
        $response->assertHeaderContains('Cache-Control', 'private');
        $response->assertHeaderContains('Cache-Control', 'max-age=86400');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Length', '19');
        $this->assertStringContainsString('fake-binary-content', $response->streamedContent());
    }

    public function test_stream_route_returns_single_byte_range(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id), ['Range' => 'bytes=2-5']);

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 2-5/10');
        $response->assertHeader('Content-Length', '4');
        $this->assertSame('2345', $response->streamedContent());
    }

    public function test_stream_route_returns_open_ended_range(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id), ['Range' => 'bytes=7-']);

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 7-9/10');
        $this->assertSame('789', $response->streamedContent());
    }

    public function test_stream_route_returns_suffix_range(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id), ['Range' => 'bytes=-4']);

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 6-9/10');
        $this->assertSame('6789', $response->streamedContent());
    }

    public function test_stream_route_returns_multiple_byte_ranges(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id), ['Range' => 'bytes=2-3,7-8']);

        $response->assertStatus(206);
        $response->assertHeaderContains('Content-Type', 'multipart/byteranges');
        $this->assertStringContainsString('bytes 2-3/10', $response->streamedContent());
        $this->assertStringContainsString('bytes 7-8/10', $response->streamedContent());
        $this->assertStringContainsString('23', $response->streamedContent());
        $this->assertStringContainsString('78', $response->streamedContent());
    }

    public function test_stream_route_ignores_range_with_too_many_parts(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $range = 'bytes='.implode(',', array_fill(0, 100, '0-'));

        $response = $this->get(route('video.stream', $video->id), ['Range' => $range]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Length', '10');
        $this->assertSame('0123456789', $response->streamedContent());
    }

    public function test_stream_route_without_x_accel_streams_through_php(): void
    {
        config(['filesystems.video_x_accel' => false]);
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id));

        $response->assertStatus(200);
        $response->assertHeaderMissing('X-Accel-Redirect');
        $this->assertSame('0123456789', $response->streamedContent());
    }

    public function test_stream_route_with_x_accel_delegates_file_to_nginx(): void
    {
        config(['filesystems.video_x_accel' => true]);
        Storage::fake('public');
        Storage::disk('public')->put('videos/my clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/my clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id), ['Range' => 'bytes=2-5']);

        $response->assertStatus(200);
        $response->assertHeader('X-Accel-Redirect', '/protected-videos/videos/my%20clip.mp4');
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Content-Disposition', 'inline; filename="my clip.mp4"');
        $response->assertHeaderContains('Cache-Control', 'private');
        $response->assertHeaderMissing('Content-Range');
        $this->assertSame('', $response->getContent());
    }

    public function test_stream_route_with_x_accel_uses_configured_location(): void
    {
        config([
            'filesystems.video_x_accel' => true,
            'filesystems.video_x_accel_location' => '/internal-media',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $this->get(route('video.stream', $video->id))
            ->assertHeader('X-Accel-Redirect', '/internal-media/videos/clip.mp4');
    }

    public function test_stream_route_with_x_accel_returns_404_for_missing_file(): void
    {
        config(['filesystems.video_x_accel' => true]);
        Storage::fake('public');

        $video = Video::factory()->create([
            'file_path' => 'videos/missing.mp4',
            'url' => '',
        ]);

        $this->get(route('video.stream', $video->id))
            ->assertStatus(404)
            ->assertHeaderMissing('X-Accel-Redirect');
    }

    public function test_stream_route_streams_remote_disk_without_local_path(): void
    {
        config(['filesystems.video_x_accel' => true]);

        $disk = $this->mockRemoteDisk(fn () => popen('printf 0123456789', 'r'));

        Storage::shouldReceive('disk')->andReturn($disk);

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $full = $this->get(route('video.stream', $video->id));

        $full->assertStatus(200);
        $full->assertHeaderMissing('X-Accel-Redirect');
        $full->assertHeader('Content-Length', '10');
        $this->assertSame('0123456789', $full->streamedContent());

        $partial = $this->get(route('video.stream', $video->id), ['Range' => 'bytes=6-8']);

        $partial->assertStatus(206);
        $partial->assertHeader('Content-Range', 'bytes 6-8/10');
        $this->assertSame('678', $partial->streamedContent());
    }

    protected function mockRemoteDisk(callable $openStream): FilesystemAdapter
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->with('videos/clip.mp4')->andReturn(true);
        $disk->shouldReceive('getAdapter')->andReturn(Mockery::mock(FlysystemAdapter::class));
        $disk->shouldReceive('size')->with('videos/clip.mp4')->andReturn(10);
        $disk->shouldReceive('readStream')->with('videos/clip.mp4')->andReturnUsing($openStream);
        $disk->shouldNotReceive('path');

        return $disk;
    }

    public function test_stream_route_returns_unsatisfiable_range(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', '0123456789');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id), ['Range' => 'bytes=50-60']);

        $response->assertStatus(416);
        $response->assertHeader('Content-Range', 'bytes */10');
    }

    public function test_stream_route_returns_304_on_matching_etag(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', 'fake-binary-content');

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.stream', $video->id));
        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);

        $response304 = $this->get(route('video.stream', $video->id), ['If-None-Match' => $etag]);

        $response304->assertStatus(304);
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
            'rotation' => 90,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('data-video-player', false);
        $response->assertSee('rotate(90deg)', false);
        $response->assertSee('data-video-play-trigger', false);
        $response->assertSee('class="rotated-media absolute"', false);
        $response->assertSee('controlsList', false);
        $response->assertSee('nodownload', false);
        $response->assertSee('disablepictureinpicture', false);
        $response->assertSee('oncontextmenu="return false"', false);
    }

    public function test_index_renders_counterclockwise_rotation(): void
    {
        Storage::fake('public');

        $video = Video::factory()->create([
            'title' => 'Против часовой',
            'type' => 'vertical',
            'file_path' => 'videos/clip.mp4',
            'url' => '',
            'rotation' => -90,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('data-video-player', false);
        $response->assertSee('rotate(-90deg)', false);
        $response->assertDontSee('rotate(90deg)', false);
    }

    public function test_index_does_not_render_rotation_when_disabled(): void
    {
        Storage::fake('public');

        $video = Video::factory()->create([
            'title' => 'Обычное видео',
            'type' => 'vertical',
            'file_path' => 'videos/clip.mp4',
            'url' => '',
            'rotation' => 0,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertDontSee('rotate(90deg)', false);
        $response->assertDontSee('rotate(-90deg)', false);
        $response->assertDontSee('data-video-player', false);
    }

    public function test_index_renders_muted_when_has_sound_disabled(): void
    {
        Storage::fake('public');

        Video::factory()->create([
            'title' => 'Без звука',
            'type' => 'vertical',
            'file_path' => 'videos/clip.mp4',
            'url' => '',
            'rotation' => 0,
            'has_sound' => false,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('<video', false);
        $response->assertSee(' muted', false);
        $response->assertSee('data-video-forbid-sound', false);
    }

    public function test_index_does_not_render_muted_when_has_sound_enabled(): void
    {
        Storage::fake('public');

        Video::factory()->create([
            'title' => 'Со звуком',
            'type' => 'vertical',
            'file_path' => 'videos/clip.mp4',
            'url' => '',
            'rotation' => 0,
            'has_sound' => true,
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee('<video', false);
        $response->assertDontSee(' muted', false);
        $response->assertDontSee('data-video-forbid-sound', false);
    }
}
