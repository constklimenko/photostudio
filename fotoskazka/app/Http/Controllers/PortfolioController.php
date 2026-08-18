<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Service;
use App\Services\PageContentService;

class PortfolioController extends Controller
{
    public function index(PageContentService $pageContent)
    {
        $page = $pageContent->get('portfolio');

        $albums = Album::query()
            ->where('type', 'portfolio')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'description']);

        return view('portfolio.index', compact('page', 'albums'));
    }

    public function show(string $slug, PageContentService $pageContent)
    {
        $page = $pageContent->get('portfolio');

        $album = Album::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->with(['cover', 'photos' => fn ($q) => $q->orderBy('sort_order')->with('media'), 'videos', 'services' => fn ($q) => $q->with('cover')])
            ->firstOrFail();

        $services = Service::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'slug']);

        return view('portfolio.show', compact('page', 'album', 'services'));
    }
}
