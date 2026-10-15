<?php

namespace Tests\Feature\Http;

use App\Models\Album;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenGraphTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_open_graph_and_twitter_meta(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta property="og:site_name" content="Фотосказка">', false)
            ->assertSee('<meta property="og:title" content="Фотосказка — профессиональная фотосъёмка">', false)
            ->assertSee('<meta property="og:description" content="Профессиональная фотосъёмка для ваших важных событий. Услуги фотографа, портфолио, выпускные альбомы.">', false)
            ->assertSee('<meta property="og:image" content="'.url('web-app-manifest-512x512.png').'">', false)
            ->assertSee('<meta property="og:url" content="'.rtrim(url('/'), '/').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="twitter:title" content="Фотосказка — профессиональная фотосъёмка">', false);
    }

    public function test_blog_post_page_uses_article_type_and_cover_image(): void
    {
        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'covers/post.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $post = Post::factory()->create([
            'title' => 'Как подготовиться к съёмке',
            'slug' => 'kak-podgotovitsya',
            'excerpt' => 'Полезные советы',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'cover_media_id' => $media->getKey(),
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk()
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:title" content="Как подготовиться к съёмке — Блог — Фотосказка">', false)
            ->assertSee('<meta property="og:image" content="'.e($media->getDisplayUrl()).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.e($media->getDisplayUrl()).'">', false);
    }

    public function test_portfolio_album_page_uses_cover_image(): void
    {
        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'covers/album.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_published' => true,
            'title' => 'Свадебная съёмка',
            'cover_media_id' => $media->getKey(),
        ]);

        $response = $this->get(route('portfolio.show', $album->slug));

        $response->assertOk()
            ->assertSee('<meta property="og:image" content="'.e($media->getDisplayUrl()).'">', false);
    }

    public function test_service_page_uses_cover_image(): void
    {
        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'covers/service.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $service = Service::factory()->create([
            'title' => 'Семейная фотосессия',
            'is_published' => true,
            'cover_media_id' => $media->getKey(),
        ]);

        $response = $this->get(route('services.show', $service->catalogPath()));

        $response->assertOk()
            ->assertSee('<meta property="og:title" content="Семейная фотосессия — Фотосказка">', false)
            ->assertSee('<meta property="og:image" content="'.e($media->getDisplayUrl()).'">', false);
    }

    public function test_category_page_uses_cover_image(): void
    {
        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'covers/category.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $category = Category::factory()->create([
            'type' => 'service',
            'name' => 'Выпускные альбомы',
            'is_published' => true,
            'parent_id' => null,
            'cover_media_id' => $media->getKey(),
        ]);

        $response = $this->get(route('services.show', $category->catalogPath()));

        $response->assertOk()
            ->assertSee('<meta property="og:image" content="'.e($media->getDisplayUrl()).'">', false);
    }

    public function test_page_without_image_falls_back_to_site_default(): void
    {
        $response = $this->get(route('portfolio.index'));

        $response->assertOk()
            ->assertSee('<meta property="og:image" content="'.url('web-app-manifest-512x512.png').'">', false);
    }
}
