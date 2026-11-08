<?php

namespace Tests\Unit\Observers;

use App\Models\Album;
use App\Models\Category;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapCacheObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_category_clears_sitemap_cache(): void
    {
        Cache::put('sitemap.xml', 'cached');

        Category::factory()->create();

        $this->assertNull(Cache::get('sitemap.xml'));
    }

    public function test_updating_category_clears_sitemap_cache(): void
    {
        $category = Category::factory()->create();

        Cache::put('sitemap.xml', 'cached');
        $category->update(['name' => 'Новое имя']);

        $this->assertNull(Cache::get('sitemap.xml'));
    }

    public function test_deleting_category_clears_sitemap_cache(): void
    {
        $category = Category::factory()->create();

        Cache::put('sitemap.xml', 'cached');
        $category->delete();

        $this->assertNull(Cache::get('sitemap.xml'));
    }

    public function test_creating_service_clears_sitemap_cache(): void
    {
        Cache::put('sitemap.xml', 'cached');

        Service::factory()->create();

        $this->assertNull(Cache::get('sitemap.xml'));
    }

    public function test_creating_post_clears_sitemap_cache(): void
    {
        Cache::put('sitemap.xml', 'cached');

        Post::factory()->create();

        $this->assertNull(Cache::get('sitemap.xml'));
    }

    public function test_creating_album_clears_sitemap_cache(): void
    {
        Cache::put('sitemap.xml', 'cached');

        Album::factory()->create();

        $this->assertNull(Cache::get('sitemap.xml'));
    }
}
