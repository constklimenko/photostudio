<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->where('type', 'service')
            ->orderBy('sort_order')
            ->with(['services' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('sort_order')
                    ->with('cover');
            }])
            ->get(['id', 'name', 'slug']);

        $servicesWithoutCategory = Service::query()
            ->where('is_published', true)
            ->whereNull('category_id')
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'short_description', 'price_from']);

        return view('services.index', compact('categories', 'servicesWithoutCategory'));
    }

    public function show(string $slug)
    {
        $service = Service::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->with(['cover', 'category', 'inquiries'])
            ->firstOrFail(['id', 'category_id', 'cover_media_id', 'title', 'slug', 'short_description', 'description', 'price_from', 'seo_title', 'seo_description']);

        $serviceList = Service::query()
            ->where('is_published', true)
            ->whereKeyNot($service->id)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'slug']);

        return view('services.show', compact('service', 'serviceList'));
    }
}
