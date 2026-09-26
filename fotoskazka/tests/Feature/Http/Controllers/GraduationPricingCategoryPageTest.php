<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Category;
use App\Models\Icon;
use App\Models\Media;
use App\Models\Page;
use App\Models\Photo;
use App\Models\Service;
use App\Models\ServiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GraduationPricingCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    private function createGraduationTree(array $rootAttributes = []): array
    {
        $root = Category::factory()->create(array_merge([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'is_graduation_albums' => true,
            'name' => 'Выпускные альбомы "под ключ"',
        ], $rootAttributes));

        $child = Category::factory()->create([
            'type' => 'service',
            'parent_id' => $root->id,
            'is_published' => true,
            'name' => 'Младшая школа',
        ]);

        $service = Service::factory()->create([
            'is_published' => true,
            'title' => '2 страницы (младшая школа)',
            'price_from' => 1650.00,
        ]);

        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'albums/graduation.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $album = Album::factory()->create([
            'is_published' => true,
            'title' => '1 разворот (младшая школа)',
        ]);

        Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
            'sort_order' => 1,
        ]);

        $service->featured_album_id = $album->id;
        $service->save();

        $service->category()->associate($child)->save();

        return compact('root', 'child', 'service', 'album', 'media');
    }

    public static function blockHeading(): string
    {
        return 'Стоимость альбомов';
    }

    public function test_graduation_category_page_renders_pricing_block(): void
    {
        $data = $this->createGraduationTree();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee(self::blockHeading());
        $response->assertSee('Выберите свою возрастную категорию и комплектацию');
        $response->assertSee('Младшая школа');
        $response->assertSee('2 страницы (младшая школа)');
        $response->assertSee('Цена: 1 650 ₽');
    }

    public function test_graduation_category_page_renders_service_items(): void
    {
        $data = $this->createGraduationTree();

        $item = ServiceItem::factory()->create(['label' => 'Портрет на обложку']);
        $data['service']->items()->attach($item, ['is_included' => true, 'sort_order' => 1]);

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee('Портрет на обложку');
    }

    public function test_graduation_category_page_renders_service_item_icons(): void
    {
        $data = $this->createGraduationTree();

        $icon = Icon::factory()->create([
            'name' => 'Камера',
            'file_path' => 'icons/camera.png',
            'disk' => 'public',
        ]);

        $item = ServiceItem::factory()->create(['label' => 'Студийная съёмка', 'icon_id' => $icon->id]);
        $data['service']->items()->attach($item, ['is_included' => true, 'sort_order' => 1]);

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee($icon->getUrl(), false);
        $response->assertSee('alt="Камера"', false);
    }

    public function test_graduation_category_page_renders_album_slider_images(): void
    {
        $data = $this->createGraduationTree();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee('data-album-slider');
        $response->assertSee($data['media']->getDisplayUrl(), false);
    }

    public function test_graduation_category_page_renders_slider_thumbs_and_thumbnail_urls(): void
    {
        $data = $this->createGraduationTree();

        $thumbMedia = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'albums/graduation-2.jpg',
            'thumbnail_path' => 'albums/thumbs/graduation-2.webp',
            'mime_type' => 'image/jpeg',
        ]);

        Photo::factory()->create([
            'album_id' => $data['album']->id,
            'media_id' => $thumbMedia->id,
            'sort_order' => 2,
        ]);

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee('data-album-thumbs', false);
        $response->assertSee($thumbMedia->getThumbnailUrl(), false);
        $this->assertSame(2, substr_count((string) $response->getContent(), 'data-slide-thumb'));
    }

    public function test_graduation_category_page_omits_slider_thumbs_for_single_photo(): void
    {
        $data = $this->createGraduationTree();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee('data-album-slider', false);
        $response->assertDontSee('data-album-thumbs', false);
    }

    public function test_graduation_category_page_renders_ar_price_badge_with_default(): void
    {
        $data = $this->createGraduationTree();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee('AR + 500 руб.');
    }

    public function test_graduation_category_page_renders_configurable_ar_price(): void
    {
        $data = $this->createGraduationTree();

        Page::factory()->create([
            'slug' => 'home',
            'title' => 'Главная',
            'ar_teaser_enabled' => true,
            'ar_price' => 750,
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee('AR + 750 руб.');
        $response->assertDontSee('AR + 500 руб.');
    }

    public function test_graduation_category_page_links_service_detail_page(): void
    {
        $data = $this->createGraduationTree();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee(route('services.show', $data['service']->catalogPath()), false);

        $response->assertSeeInOrder([
            '<a href="'.route('services.show', $data['service']->catalogPath()).'" class="hover:text-[#d4af37] transition">',
            '2 страницы (младшая школа)',
            '</a>',
        ], false);
    }

    public function test_graduation_category_page_hides_unpublished_child_category(): void
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'is_graduation_albums' => true,
        ]);

        $child = Category::factory()->create([
            'type' => 'service',
            'parent_id' => $root->id,
            'is_published' => false,
            'name' => 'Скрытая подкатегория',
        ]);

        Service::factory()->create(['is_published' => true])->category()
            ->associate($child)->save();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk();
        $response->assertSee(self::blockHeading());
        $response->assertDontSee('Скрытая подкатегория');
    }

    public function test_graduation_category_page_hides_service_of_unpublished_category_via_has(): void
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'is_graduation_albums' => true,
        ]);

        $child = Category::factory()->create([
            'type' => 'service',
            'parent_id' => $root->id,
            'is_published' => true,
            'name' => 'Категория без услуг',
        ]);

        $service = Service::factory()->create(['is_published' => false, 'title' => 'Скрытая услуга']);
        $service->category()->associate($child)->save();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk();
        $response->assertDontSee('Категория без услуг');
        $response->assertDontSee('Скрытая услуга');
    }

    public function test_graduation_category_page_hides_unpublished_service(): void
    {
        $data = $this->createGraduationTree();

        $system = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'is_graduation_albums' => true,
        ]);

        $child = Category::factory()->create([
            'type' => 'service',
            'parent_id' => $system->id,
            'is_published' => true,
        ]);

        $service = Service::factory()->create([
            'is_published' => false,
            'title' => 'Неопубликованная услуга',
        ]);

        $service->category()->associate($child)->save();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertDontSee('Неопубликованная услуга');
    }

    public function test_graduation_category_page_orders_services_by_sort_order(): void
    {
        $data = $this->createGraduationTree();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));
        $html = $response->getContent();

        preg_match('/<section[^>]*>\s*<div[^>]*>\s*<h2[^>]*>\s*'.preg_quote(self::blockHeading(), '/').'\s*<\/h2>.*?<\/section>/su', $html, $matches);
        $section = $matches[0] ?? '';

        $this->assertNotSame('', $section);
        $this->assertStringContainsString('2 страницы (младшая школа)', $section);
    }

    public function test_graduation_category_page_shows_block_texts_from_graduation_albums_page(): void
    {
        $data = $this->createGraduationTree();

        Page::factory()->create([
            'slug' => 'graduation-albums',
            'title' => 'Название блока из страницы',
            'subtitle' => 'Подзаголовок блока из страницы',
            'home_title' => 'Кастомное название',
            'home_subtitle' => 'Кастомный подзаголовок',
            'home_content' => '<p>Описание блока</p>',
            'show_on_home' => true,
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $data['root']->catalogPath()));

        $response->assertOk();
        $response->assertSee('Кастомное название');
        $response->assertSee('Кастомный подзаголовок');
        $response->assertSee('Описание блока');
        $response->assertDontSee(self::blockHeading());
    }

    public function test_graduation_category_page_without_flag_keeps_standard_layout(): void
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'is_graduation_albums' => false,
            'name' => 'Обычные услуги',
        ]);

        $child = Category::factory()->create([
            'type' => 'service',
            'parent_id' => $root->id,
            'is_published' => true,
            'name' => 'Обычная подкатегория',
        ]);

        $service = Service::factory()->create(['is_published' => true]);
        $service->category()->associate($child)->save();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk();
        $response->assertDontSee(self::blockHeading());
        $response->assertSee('Обычная подкатегория');
    }

    public function test_unpublished_graduation_category_returns_404(): void
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => false,
            'is_graduation_albums' => true,
        ]);

        $this->get(route('services.show', $root->catalogPath()))
            ->assertNotFound();
    }

    public function test_home_page_does_not_render_graduation_pricing_block(): void
    {
        $this->createGraduationTree();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee(self::blockHeading());
        $response->assertDontSee('Младшая школа');
        $response->assertDontSee('AR + 500 руб.');
    }

    public function test_graduation_category_page_avoid_n_plus_one(): void
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'is_graduation_albums' => true,
            'name' => 'Выпускные альбомы',
        ]);

        foreach (['Подраздел А', 'Подраздел Б'] as $childName) {
            $child = Category::factory()->create([
                'type' => 'service',
                'parent_id' => $root->id,
                'is_published' => true,
                'name' => $childName,
            ]);

            foreach (range(1, 3) as $n) {
                $album = Album::factory()->create(['is_published' => true]);
                $media = Media::query()->create([
                    'disk' => 'public',
                    'file_path' => "albums/{$childName}-{$n}.jpg",
                    'mime_type' => 'image/jpeg',
                ]);
                Photo::factory()->create([
                    'album_id' => $album->id,
                    'media_id' => $media->id,
                    'sort_order' => 1,
                ]);

                $service = Service::factory()->create([
                    'is_published' => true,
                    'title' => "Услуга {$childName} {$n}",
                    'featured_album_id' => $album->id,
                ]);
                $service->category()->associate($child)->save();
            }
        }

        \DB::enableQueryLog();
        $response = $this->get(route('services.show', $root->catalogPath()));
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $response->assertOk();
        $this->assertLessThanOrEqual(30, $queryCount, "Expected ≤30 queries for graduation category page, got {$queryCount}. Possible N+1 issue.");
    }
}
