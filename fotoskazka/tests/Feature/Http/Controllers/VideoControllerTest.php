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

        $video = Video::factory()->create([
            'file_path' => 'videos/clip.mp4',
            'url' => '',
        ]);

        $response = $this->get(route('video.index'));

        $response->assertSee(Storage::url($video->file_path));
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
}
