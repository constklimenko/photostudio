<?php

namespace App\Http\Controllers;

use App\Enums\ShootingAlbumDisplay;
use App\Models\Album;
use App\Models\Category;
use App\Models\Service;
use App\Services\PageContentService;
use App\Services\ServiceCatalogResolver;
use Illuminate\Support\Collection;
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
            ->with(['cover', 'children' => fn ($query) => $query->where('is_published', true)->orderBy('sort_order')->with(['cover', 'parent'])])
            ->with(['services' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('sort_order')
                    ->with(['cover', 'items.icon', 'category']);
            }])
            ->get(['id', 'parent_id', 'cover_media_id', 'name', 'slug', 'description', 'price_from']);

        $servicesWithoutCategory = Service::query()
            ->where('is_published', true)
            ->whereNull('category_id')
            ->orderBy('sort_order')
            ->with(['cover', 'items.icon', 'category'])
            ->get(['id', 'cover_media_id', 'title', 'slug', 'category_id', 'short_description', 'price_from', 'price_note']);

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

        $service->load([
            'cover',
            'category',
            'items.icon',
            'videos',
            'ctaAlbum',
            'albums' => function ($query) use ($service) {
                $query->where('is_published', true);

                if ($service->show_album_photos && $service->featured_album_id) {
                    $query->whereKeyNot($service->featured_album_id);
                }

                $query->with(['cover', 'videos']);
            },
            'shootingAlbum' => function ($query) use ($service) {
                $query->where('is_published', true);

                if ($service->shooting_album_display === ShootingAlbumDisplay::Grid) {
                    $query->with('photos.media');
                } else {
                    $query->with('cover');
                }
            },
        ]);

        if ($service->show_album_photos && $service->featured_album_id) {
            $service->load([
                'featuredAlbum' => fn ($query) => $query->where('is_published', true),
            ]);

            if ($service->featuredAlbum) {
                $service->featuredAlbum->load('photos.media');
            }
        }

        $serviceList = Service::query()
            ->where('is_published', true)
            ->whereKeyNot($service->id)
            ->inRandomOrder()
            ->limit(3)
            ->with(['cover', 'category'])
            ->get(['id', 'cover_media_id', 'title', 'slug', 'category_id']);

        return view('services.show', compact('page', 'service', 'serviceList'));
    }

    private function showCategory(Category $category): View
    {
        $page = $this->pageContent->get('services');

        $isGraduation = (bool) $category->is_graduation_albums;

        $category->load([
            'cover',
            'parent',
            'children' => function ($query) use ($isGraduation) {
                $query->where('is_published', true)->orderBy('sort_order')->with(['cover']);

                if ($isGraduation) {
                    $query
                        ->whereHas('services', fn ($sq) => $sq->where('is_published', true))
                        ->with([
                            'services' => fn ($sq) => $sq
                                ->where('is_published', true)
                                ->orderBy('sort_order')
                                ->with([
                                    'items.icon',
                                    'category.parent',
                                    'featuredAlbum' => fn ($fq) => $fq
                                        ->where('is_published', true)
                                        ->with(['photos' => fn ($pq) => $pq->orderBy('sort_order')->with('media')]),
                                ]),
                        ]);
                }
            },
            'services' => fn ($query) => $query->where('is_published', true)->orderBy('sort_order')->with(['cover', 'items']),
            'videos',
            'items.icon',
            'ctaAlbum',
            'albums' => function ($query) use ($category) {
                $query->where('is_published', true);

                if ($category->show_album_photos && $category->featured_album_id) {
                    $query->whereKeyNot($category->featured_album_id);
                }

                $query->with(['cover', 'videos']);
            },
            'shootingAlbum' => function ($query) use ($category) {
                $query->where('is_published', true);

                if ($category->shooting_album_display === ShootingAlbumDisplay::Grid) {
                    $query->with('photos.media');
                } else {
                    $query->with('cover');
                }
            },
        ]);

        if ($category->show_album_photos && $category->featured_album_id) {
            $category->load([
                'featuredAlbum' => fn ($query) => $query->where('is_published', true),
            ]);

            if ($category->featuredAlbum) {
                $category->featuredAlbum->load('photos.media');
            }
        }

        $graduationCategories = $isGraduation ? collect([$category]) : collect();
        $graduationBlock = [
            'title' => 'Стоимость альбомов',
            'subtitle' => 'Выберите свою возрастную категорию и комплектацию',
            'content' => null,
        ];
        $arPrice = 500;
        $arTeaser = ['enabled' => false];
        $shootingWorks = collect();
        $shootingBlock = [];

        if ($isGraduation) {
            $graduationPage = $this->pageContent->get('graduation-albums');

            if ($graduationPage) {
                $graduationBlock = [
                    'title' => $graduationPage->home_title ?: ($graduationPage->title ?: $graduationBlock['title']),
                    'subtitle' => $graduationPage->home_subtitle ?: ($graduationPage->subtitle ?: $graduationBlock['subtitle']),
                    'content' => $this->plainText($graduationPage->home_content ?: $graduationPage->content),
                ];
            }

            $arPrice = (int) ($this->pageContent->get('home')?->ar_price ?? $arPrice);
            $arTeaser = $this->pageContent->arTeaser();
            $shootingWorks = $this->shootingWorks();
            $shootingBlock = $this->shootingBlock();
        }

        return view('services.category', compact(
            'page',
            'category',
            'graduationCategories',
            'graduationBlock',
            'arPrice',
            'arTeaser',
            'shootingWorks',
            'shootingBlock',
        ));
    }

    private function shootingWorks(): Collection
    {
        return Album::query()
            ->where('type', 'behind_the_scenes')
            ->where('is_featured', true)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'description', 'sort_order']);
    }

    private function shootingBlock(): array
    {
        $block = [
            'title' => 'Фото со съёмок',
            'subtitle' => 'Загляните на съёмочную площадку',
            'content' => null,
        ];

        $shootingPage = $this->pageContent->get('shooting');

        if (! $shootingPage) {
            return $block;
        }

        return [
            'title' => $shootingPage->home_title ?: ($shootingPage->title ?: $block['title']),
            'subtitle' => $shootingPage->home_subtitle ?: ($shootingPage->subtitle ?: $block['subtitle']),
            'content' => $this->plainText($shootingPage->home_content ?: $shootingPage->content),
        ];
    }

    private function plainText(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        $text = trim(strip_tags($html));

        return $text === '' ? null : $text;
    }
}
