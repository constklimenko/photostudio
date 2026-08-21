<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageContentService
{
    public function get(string $slug): ?Page
    {
        $cacheKey = "page_content_{$slug}";

        $data = Cache::rememberForever($cacheKey, function () use ($slug) {
            return Page::where('slug', $slug)
                ->where('is_published', true)
                ->first()
                ?->withoutRelations()
                ?->toArray();
        });

        return $data ? Page::hydrate([$data])->first() : null;
    }

    public function getHomeSections(): array
    {
        $data = Cache::rememberForever('pages_home_sections', function () {
            return Page::where('show_on_home', true)
                ->where('is_published', true)
                ->orderBy('home_sort_order')
                ->get()
                ->map->withoutRelations()
                ->map->toArray()
                ->all();
        });

        return collect($data)
            ->keyBy('slug')
            ->map(fn (array $attrs) => Page::hydrate([$attrs])->first())
            ->all();
    }

    public function getMenuItems(): array
    {
        return Cache::rememberForever('pages_menu', function () {
            return Page::where('is_published', true)
                ->whereIn('slug', ['home', 'services', 'portfolio', 'blog', 'video'])
                ->orderBy('sort_order')
                ->get(['slug', 'title', 'menu_title'])
                ->map(fn (Page $page) => [
                    'slug' => $page->slug,
                    'title' => $page->menu_title ?: $page->title,
                    'url' => match ($page->slug) {
                        'home' => '/',
                        default => "/{$page->slug}",
                    },
                ])
                ->all();
        });
    }

    public function clearCache(?string $slug = null): void
    {
        if ($slug) {
            Cache::forget("page_content_{$slug}");
        } else {
            Cache::flush();
        }
    }
}
