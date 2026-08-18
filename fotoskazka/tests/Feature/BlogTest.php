<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_returns_successful_response(): void
    {
        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
    }

    public function test_blog_index_shows_published_posts(): void
    {
        $category = Category::factory()->create(['type' => 'post']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertSee($post->title);
    }

    public function test_blog_index_hides_unpublished_posts(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'is_published' => false,
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertDontSee($post->title);
    }

    public function test_blog_index_hides_future_posts(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->addDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertDontSee($post->title);
    }

    public function test_blog_index_filters_by_search(): void
    {
        $matching = Post::factory()->create([
            'title' => 'Unique search term match',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('blog.index', ['q' => 'Unique']));

        $response->assertSee($matching->title);
    }

    public function test_blog_index_filters_by_category(): void
    {
        $category = Category::factory()->create(['type' => 'post']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('blog.index', ['category' => $category->slug]));

        $response->assertSee($post->title);
    }

    public function test_blog_show_returns_successful_response(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee($post->title);
    }

    public function test_blog_show_returns_404_for_unpublished(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'is_published' => false,
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertNotFound();
    }

    public function test_blog_show_returns_404_for_future(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->addDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertNotFound();
    }

    public function test_blog_show_has_inquiry_form(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertSee('Записаться на съёмку');
        $response->assertSee('name="name"', false);
        $response->assertSee('name="phone"', false);
    }

    public function test_blog_show_displays_attached_videos(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'is_published' => true,
            'title' => 'Итоги сезона',
        ]);
        $video = Video::factory()->create([
            'type' => 'horizontal',
            'title' => 'Закулисье съёмки',
        ]);
        $post->videos()->attach($video);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertSee('Закулисье съёмки');
        $response->assertSee($video->embed_url);
    }

    public function test_blog_show_displays_vertical_attached_videos(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);
        $video = Video::factory()->create([
            'type' => 'vertical',
            'title' => 'Reels из блога',
        ]);
        $post->videos()->attach($video);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertSee('Reels из блога');
        $response->assertSee($video->embed_url);
    }
}
