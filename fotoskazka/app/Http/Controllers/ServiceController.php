<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Services\PageContentService;

class ServiceController extends Controller
{
    public function index(PageContentService $pageContent)
    {
        $page = $pageContent->get('services');

        $categories = Category::query()
            ->where('type', 'service')
            ->orderBy('sort_order')
            ->with(['services' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('sort_order')
                    ->with(['cover', 'items']);
            }])
            ->get(['id', 'name', 'slug']);

        $servicesWithoutCategory = Service::query()
            ->where('is_published', true)
            ->whereNull('category_id')
            ->orderBy('sort_order')
            ->with(['cover', 'items'])
            ->get(['id', 'cover_media_id', 'title', 'slug', 'short_description', 'price_from', 'price_note']);

        return view('services.index', compact('page', 'categories', 'servicesWithoutCategory'));
    }

    public function show(string $slug, PageContentService $pageContent)
    {
        $page = $pageContent->get('services');

        $service = Service::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->with(['cover', 'category', 'items', 'videos', 'albums' => fn ($q) => $q->where('is_published', true)->with(['cover', 'videos'])])
            ->firstOrFail(['id', 'category_id', 'cover_media_id', 'title', 'slug', 'short_description', 'description', 'price_from', 'price_note', 'seo_title', 'seo_description']);

        $serviceList = Service::query()
            ->where('is_published', true)
            ->whereKeyNot($service->id)
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug']);

        return view('services.show', compact('page', 'service', 'serviceList'));
    }
}
