<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Services\PageContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class HeaderMenuTest extends TestCase
{
    use RefreshDatabase;

    private function seedMenuPages(): void
    {
        foreach (['home', 'services', 'portfolio', 'blog', 'video'] as $i => $slug) {
            Page::factory()->create([
                'slug' => $slug,
                'title' => ucfirst($slug),
                'menu_title' => ucfirst($slug),
                'is_published' => true,
                'sort_order' => $i + 1,
            ]);
        }

        Cache::flush();
        View::share('menuItems', app(PageContentService::class)->getMenuItems());
    }

    public function test_current_page_link_is_hidden_on_section_pages(): void
    {
        $this->seedMenuPages();

        $html = $this->get(route('services.index'))->getContent();

        $this->assertStringNotContainsString('href="/services"', $html);
        foreach (['/portfolio', '/blog', '/video', '/'] as $url) {
            $this->assertStringContainsString('href="'.$url.'"', $html);
        }
    }

    public function test_current_page_link_is_hidden_on_home(): void
    {
        $this->seedMenuPages();

        $html = $this->get(route('home'))->getContent();

        $this->assertSame(1, substr_count($html, 'href="/"'));
        $this->assertStringContainsString('href="/services"', $html);
    }

    public function test_current_page_link_is_hidden_on_nested_pages(): void
    {
        $this->seedMenuPages();

        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $html = $this->get(route('blog.show', $post->slug))->getContent();

        $this->assertStringNotContainsString('href="/blog"', $html);
        $this->assertStringContainsString('href="'.route('blog.index').'"', $html);
    }
}
