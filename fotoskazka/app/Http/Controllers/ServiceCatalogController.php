<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Services\PageContentService;
use App\Services\ServiceCatalogResolver;
use Illuminate\View\View;

class ServiceCatalogController extends Controller
{
    public function __construct(
        private readonly ServiceCatalogResolver $resolver,
        private readonly PageContentService $pageContent,
    ) {}

    public function index(): View
    {
        $page = $this->pageContent->get('services');

        $categories = Category::query()
            ->where('type', 'service')
            ->where('is_published', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['cover', 'children' => fn ($query) => $query->where('is_published', true)->orderBy('sort_order')])
            ->with(['services' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('sort_order')
                    ->with(['cover', 'items']);
            }])
            ->get(['id', 'parent_id', 'cover_media_id', 'name', 'slug', 'description', 'price_from']);

        $servicesWithoutCategory = Service::query()
            ->where('is_published', true)
            ->whereNull('category_id')
            ->orderBy('sort_order')
            ->with(['cover', 'items'])
            ->get(['id', 'cover_media_id', 'title', 'slug', 'short_description', 'price_from', 'price_note']);

        return view('services.index', compact('page', 'categories', 'servicesWithoutCategory'));
    }

    public function show(string $path): View
    {
        $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));

        $entity = $this->resolver->resolve($segments);

        abort_unless($entity instanceof Category || $entity instanceof Service, 404);

        return $entity instanceof Service
            ? $this->showService($entity)
            : $this->showCategory($entity);
    }

    private function showService(Service $service): View
    {
        $page = $this->pageContent->get('services');

        $service->load(['cover', 'category', 'items', 'videos', 'albums' => fn ($query) => $query->where('is_published', true)->with(['cover', 'videos'])]);

        $serviceList = Service::query()
            ->where('is_published', true)
            ->whereKeyNot($service->id)
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'category_id']);

        return view('services.show', compact('page', 'service', 'serviceList'));
    }

    private function showCategory(Category $category): View
    {
        $page = $this->pageContent->get('services');

        $category->load([
            'cover',
            'parent',
            'children' => fn ($query) => $query->where('is_published', true)->orderBy('sort_order')->with(['cover']),
            'services' => fn ($query) => $query->where('is_published', true)->orderBy('sort_order')->with(['cover', 'items']),
        ]);

        return view('services.category', compact('page', 'category'));
    }
}
