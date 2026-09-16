<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Category;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Fluent;

class SitemapService
{
    public const CACHE_KEY = 'sitemap.xml';

    public function render(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(24), fn (): string => $this->build());
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function build(): string
    {
        $urls = collect();

        $urls->push($this->entry(url('/'), now()));
        $urls->push($this->entry(url('/services'), now()));
        $urls->push($this->entry(url('/portfolio'), now()));
        $urls->push($this->entry(url('/blog'), now()));

        Category::query()
            ->where('type', 'service')
            ->where('is_published', true)
            ->get()
            ->each(function (Category $category) use ($urls) {
                $urls->push($this->entry(
                    url('/services/'.$category->catalogPath()),
                    $category->updated_at
                ));
            });

        Service::query()
            ->with('category')
            ->where('is_published', true)
            ->get()
            ->each(function (Service $service) use ($urls) {
                $urls->push($this->entry(
                    url('/services/'.$service->catalogPath()),
                    $service->updated_at
                ));
            });

        Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get()
            ->each(function (Post $post) use ($urls) {
                $urls->push($this->entry(
                    url('/blog/'.$post->slug),
                    $post->updated_at
                ));
            });

        Album::query()
            ->where('type', 'portfolio')
            ->where('is_published', true)
            ->get()
            ->each(function (Album $album) use ($urls) {
                $urls->push($this->entry(
                    url('/portfolio/'.$album->slug),
                    $album->updated_at
                ));
            });

        return $this->renderXml($urls);
    }

    private function renderXml(Collection $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        $urls->each(function (Fluent $url) use (&$lines) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.e($url->loc).'</loc>';
            $lines[] = '    <lastmod>'.$url->lastmod->toDateString().'</lastmod>';
            $lines[] = '  </url>';
        });

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    private function entry(string $loc, object $lastmod): Fluent
    {
        return new Fluent([
            'loc' => $loc,
            'lastmod' => $lastmod,
        ]);
    }
}
