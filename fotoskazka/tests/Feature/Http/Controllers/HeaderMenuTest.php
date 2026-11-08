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

    public function test_page_hidden_from_menu_when_show_in_menu_false(): void
    {
        $this->seedMenuPages();

        Page::factory()->create([
            'slug' => 'gallery',
            'title' => 'Галерея',
            'menu_title' => 'Галерея',
            'is_published' => true,
            'show_in_menu' => false,
            'sort_order' => 10,
        ]);

        Cache::flush();
        View::share('menuItems', app(PageContentService::class)->getMenuItems());

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('href="/gallery"', $html);
    }

    public function test_custom_page_shown_in_menu_when_enabled(): void
    {
        $this->seedMenuPages();

        Page::factory()->create([
            'slug' => 'about',
            'title' => 'О нас',
            'menu_title' => 'О нас',
            'is_published' => true,
            'show_in_menu' => true,
            'sort_order' => 9,
        ]);

        Cache::flush();
        View::share('menuItems', app(PageContentService::class)->getMenuItems());

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('href="/about"', $html);
    }

    public function test_unpublished_page_hidden_from_menu(): void
    {
        $this->seedMenuPages();

        Page::factory()->create([
            'slug' => 'drafts',
            'title' => 'Черновик',
            'menu_title' => 'Черновик',
            'is_published' => false,
            'show_in_menu' => true,
            'sort_order' => 11,
        ]);

        Cache::flush();
        View::share('menuItems', app(PageContentService::class)->getMenuItems());

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('href="/drafts"', $html);
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
