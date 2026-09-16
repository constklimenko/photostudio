<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Category;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_returns_plain_text(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'text/plain');
    }

    public function test_robots_txt_contains_expected_directives(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /admin');
        $response->assertSee('Disallow: /cabinet');
        $response->assertSee('Disallow: /login');
        $response->assertSee('Disallow: /logout');
        $response->assertSee('Disallow: /media/*/original');
        $response->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_robots_txt_does_not_block_public_sections(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertDontSee('Disallow: /services');
        $response->assertDontSee('Disallow: /portfolio');
        $response->assertDontSee('Disallow: /blog');
    }

    public function test_sitemap_returns_application_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_sitemap_contains_home_and_section_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee('<loc>'.url('/').'</loc>', false);
        $response->assertSee('<loc>'.url('/services').'</loc>', false);
        $response->assertSee('<loc>'.url('/portfolio').'</loc>', false);
        $response->assertSee('<loc>'.url('/blog').'</loc>', false);
    }

    public function test_sitemap_contains_published_service_categories_and_services(): void
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'slug' => 'vypusknye-albomy',
            'is_published' => true,
        ]);

        Category::factory()->create([
            'type' => 'service',
            'slug' => 'dlya-shkol',
            'parent_id' => $root->id,
            'is_published' => true,
        ]);

        Service::factory()->create([
            'category_id' => Category::where('slug', 'dlya-shkol')->sole()->id,
            'slug' => 'klassika',
            'is_published' => true,
        ]);

        Service::factory()->create([
            'category_id' => null,
            'slug' => 'individualnaya-semka',
            'is_published' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertSee('<loc>'.url('/services/vypusknye-albomy').'</loc>', false);
        $response->assertSee('<loc>'.url('/services/vypusknye-albomy/dlya-shkol').'</loc>', false);
        $response->assertSee('<loc>'.url('/services/vypusknye-albomy/dlya-shkol/klassika').'</loc>', false);
        $response->assertSee('<loc>'.url('/services/individualnaya-semka').'</loc>', false);
    }

    public function test_sitemap_skips_unpublished_categories_and_services(): void
    {
        $published = Category::factory()->create([
            'type' => 'service',
            'slug' => 'published-cat',
            'is_published' => true,
        ]);

        Category::factory()->create([
            'type' => 'service',
            'slug' => 'hidden-cat',
            'is_published' => false,
        ]);

        Service::factory()->create([
            'category_id' => $published->id,
            'slug' => 'shown-service',
            'is_published' => true,
        ]);

        Service::factory()->create([
            'category_id' => $published->id,
            'slug' => 'draft-service',
            'is_published' => false,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertSee('<loc>'.url('/services/published-cat/shown-service').'</loc>', false);
        $response->assertDontSee(url('/services/hidden-cat'));
        $response->assertDontSee(url('/services/published-cat/draft-service'));
    }

    public function test_sitemap_contains_only_published_and_past_blog_posts(): void
    {
        Post::factory()->create([
            'slug' => 'published-post',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Post::factory()->create([
            'slug' => 'draft-post',
            'is_published' => false,
            'published_at' => now()->subDay(),
        ]);

        Post::factory()->create([
            'slug' => 'scheduled-post',
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        Post::factory()->create([
            'slug' => 'no-date-post',
            'is_published' => true,
            'published_at' => null,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertSee('<loc>'.url('/blog/published-post').'</loc>', false);
        $response->assertDontSee(url('/blog/draft-post'));
        $response->assertDontSee(url('/blog/scheduled-post'));
        $response->assertDontSee(url('/blog/no-date-post'));
    }

    public function test_sitemap_contains_published_portfolio_albums_only(): void
    {
        Album::factory()->create([
            'type' => 'portfolio',
            'slug' => 'vypusk-2025',
            'is_published' => true,
        ]);

        Album::factory()->create([
            'type' => 'portfolio',
            'slug' => 'hidden-album',
            'is_published' => false,
        ]);

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'slug' => 'bts-album',
            'is_published' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertSee('<loc>'.url('/portfolio/vypusk-2025').'</loc>', false);
        $response->assertDontSee(url('/portfolio/hidden-album'));
        $response->assertDontSee(url('/portfolio/bts-album'));
    }

    public function test_sitemap_uses_updated_at_as_lastmod(): void
    {
        $post = Post::factory()->create([
            'slug' => 'post-1',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertSee('<lastmod>'.$post->updated_at->toDateString().'</lastmod>', false);
    }

    public function test_sitemap_is_cached(): void
    {
        Post::factory()->create([
            'slug' => 'post-1',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/sitemap.xml');

        $this->assertNotNull(Cache::get('sitemap.xml'));
    }
}
