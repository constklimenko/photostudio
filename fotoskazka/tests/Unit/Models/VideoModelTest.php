<?php

namespace Tests\Unit\Models;

use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoModelTest extends TestCase
{
    public function test_casts_is_active_to_boolean(): void
    {
        $video = new Video(['is_active' => 1]);

        $this->assertTrue($video->is_active);
    }

    public function test_casts_show_on_home_to_boolean(): void
    {
        $video = new Video(['show_on_home' => 1]);

        $this->assertTrue($video->show_on_home);
    }

    public function test_casts_sort_order_to_integer(): void
    {
        $video = new Video(['sort_order' => '5']);

        $this->assertSame(5, $video->sort_order);
    }

    public function test_embed_url_returns_storage_url_for_uploaded_file(): void
    {
        $video = new Video(['file_path' => 'videos/clip.mp4', 'url' => 'https://example.com/video']);

        $this->assertEquals(Storage::url('videos/clip.mp4'), $video->embed_url);
    }

    public function test_embed_url_returns_null_when_no_url_and_no_file(): void
    {
        $video = new Video(['url' => '', 'file_path' => null]);

        $this->assertNull($video->embed_url);
    }

    public function test_embed_url_converts_youtube_watch_url(): void
    {
        $video = new Video(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $video->embed_url);
    }

    public function test_embed_url_converts_youtube_embed_url(): void
    {
        $video = new Video(['url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ']);

        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $video->embed_url);
    }

    public function test_embed_url_converts_youtu_be_short_url(): void
    {
        $video = new Video(['url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $video->embed_url);
    }

    public function test_embed_url_converts_vimeo_url(): void
    {
        $video = new Video(['url' => 'https://vimeo.com/76979871']);

        $this->assertEquals('https://player.vimeo.com/video/76979871', $video->embed_url);
    }

    public function test_embed_url_converts_player_vimeo_url(): void
    {
        $video = new Video(['url' => 'https://player.vimeo.com/video/76979871']);

        $this->assertEquals('https://player.vimeo.com/video/76979871', $video->embed_url);
    }

    public function test_embed_url_converts_rutube_url(): void
    {
        $video = new Video(['url' => 'https://rutube.ru/video/abcdef123']);

        $this->assertEquals('https://rutube.ru/play/embed/abcdef123', $video->embed_url);
    }

    public function test_embed_url_converts_vkvideo_clip_url(): void
    {
        $video = new Video(['url' => 'https://vkvideo.ru/clip-208625881_456239018']);

        $this->assertEquals('https://vk.com/video_ext.php?oid=-208625881&id=456239018', $video->embed_url);
    }

    public function test_embed_url_converts_vk_video_url(): void
    {
        $video = new Video(['url' => 'https://vk.com/video-208625881_456239018']);

        $this->assertEquals('https://vk.com/video_ext.php?oid=-208625881&id=456239018', $video->embed_url);
    }

    public function test_embed_url_passes_through_vk_video_ext_url(): void
    {
        $url = 'https://vk.com/video_ext.php?oid=-208625881&id=456239018';
        $video = new Video(['url' => $url]);

        $this->assertEquals($url, $video->embed_url);
    }

    public function test_embed_url_returns_unknown_url_as_is(): void
    {
        $url = 'https://example.com/videos/clip.mp4';
        $video = new Video(['url' => $url]);

        $this->assertEquals($url, $video->embed_url);
    }

    public function test_is_upload_true_when_file_path_exists(): void
    {
        $video = new Video(['file_path' => 'videos/clip.mp4']);

        $this->assertTrue($video->is_upload);
    }

    public function test_is_upload_false_when_no_file_path(): void
    {
        $video = new Video(['file_path' => null, 'url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->assertFalse($video->is_upload);
    }

    public function test_source_url_returns_storage_url_for_uploaded_file(): void
    {
        $video = new Video(['file_path' => 'videos/clip.mp4', 'url' => 'https://example.com/video']);

        $this->assertEquals(Storage::url('videos/clip.mp4'), $video->source_url);
    }

    public function test_source_url_returns_embed_url_for_external_url(): void
    {
        $video = new Video(['file_path' => null, 'url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->assertEquals('https://youtu.be/dQw4w9WgXcQ', $video->source_url);
    }

    public function test_source_url_returns_url_when_empty_string(): void
    {
        $video = new Video(['url' => '', 'file_path' => null]);

        $this->assertSame('', $video->source_url);
    }

    public function test_source_url_returns_null_when_url_is_null(): void
    {
        $video = new Video(['url' => null, 'file_path' => null]);

        $this->assertNull($video->source_url);
    }
}
