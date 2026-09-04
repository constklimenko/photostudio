<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Photo;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ServiceCatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    private Category $albums;

    private Category $schools;

    private Service $classic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->albums = Category::factory()->create([
            'type' => 'service',
            'slug' => 'vypusknye-albomy',
            'parent_id' => null,
            'is_published' => true,
            'name' => 'Выпускные альбомы',
            'description' => '<p>Описание выпускных альбомов</p>',
            'price_from' => 12000,
            'seo_title' => 'Выпускные альбомы — SEO',
        ]);

        $this->schools = Category::factory()->create([
            'type' => 'service',
            'slug' => 'dlya-shkol',
            'parent_id' => $this->albums->id,
            'is_published' => true,
            'name' => 'Для школ',
            'description' => '<p>Описание для школ</p>',
            'price_from' => 8000,
        ]);

        $this->classic = Service::factory()->create([
            'slug' => 'klassika',
            'category_id' => $this->schools->id,
            'is_published' => true,
            'title' => 'Классика',
            'short_description' => 'Классический альбом',
            'price_from' => 15000,
        ]);
    }

    public function test_index_shows_root_categories(): void
    {
        $response = $this->get(route('services.index'));

        $response->assertStatus(200);
        $response->assertSee('Выпускные альбомы');
        $response->assertSee('/services/vypusknye-albomy');
    }

    public function test_index_shows_root_level_services(): void
    {
        $service = Service::factory()->create([
            'category_id' => null,
            'is_published' => true,
            'title' => 'Индивидуальная съёмка',
        ]);

        $response = $this->get(route('services.index'));

        $response->assertSee('Индивидуальная съёмка');
    }

    public function test_index_shows_child_categories_with_cover(): void
    {
        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'covers/schools-cover.jpg',
            'thumbnail_path' => null,
        ]);

        $this->schools->update(['cover_media_id' => $media->id]);

        $response = $this->get(route('services.index'));

        $response->assertStatus(200);
        $response->assertSee('Для школ');
        $response->assertSee('/services/vypusknye-albomy/dlya-shkol');
        $response->assertSee('covers/schools-cover.jpg', false);
        $response->assertSee('alt="Для школ"', false);
        $response->assertSee('Подробнее');
    }

    public function test_index_hides_unpublished_root_category(): void
    {
        Category::factory()->create([
            'type' => 'service',
            'slug' => 'hidden',
            'name' => 'Скрытый раздел',
            'is_published' => false,
        ]);

        $response = $this->get(route('services.index'));

        $response->assertDontSee('Скрытый раздел');
    }

    public function test_category_page_returns_successful_response(): void
    {
        $response = $this->get('/services/vypusknye-albomy');

        $response->assertStatus(200);
        $response->assertSee('Выпускные альбомы');
    }

    public function test_category_page_displays_content_and_cta(): void
    {
        $response = $this->get('/services/vypusknye-albomy');

        $response->assertSee('Описание выпускных альбомов');
        $response->assertSee('12 000');
        $response->assertSee('Оставить заявку');
        $response->assertSee('Выпускные альбомы — SEO');
    }

    public function test_nested_category_page_renders_full_breadcrumb(): void
    {
        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertSeeInOrder(['Главная', 'Услуги', 'Выпускные альбомы', 'Для школ']);
        $response->assertSee('/services/vypusknye-albomy');
        $response->assertSeeHtml('aria-current="page">Для школ</span>');
        $response->assertDontSee('aria-current="page">Выпускные альбомы</span>');
    }

    public function test_nested_category_page_shows_child_and_service(): void
    {
        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertStatus(200);
        $response->assertSee('Для школ');
        $response->assertSee('/services/vypusknye-albomy/dlya-shkol/klassika');
        $response->assertSee('Классика');
    }

    public function test_service_inside_nested_category(): void
    {
        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertSee('Классика');
        $response->assertSee('15 000');
    }

    public function test_service_page_keeps_existing_functionality(): void
    {
        $item = ServiceItem::factory()->create(['label' => 'Ретушь всех фото']);
        $this->classic->items()->attach($item);

        $album = Album::factory()->create(['is_published' => true, 'title' => 'Альбом образец']);
        $this->classic->albums()->attach($album);

        $video = Video::factory()->create(['type' => 'horizontal', 'title' => 'Видео процесса']);
        $this->classic->videos()->attach($video);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertSee('Ретушь всех фото');
        $response->assertSee('Альбом образец');
        $response->assertSee('Видео процесса');
        $response->assertSee('Оставить заявку');
    }

    public function test_service_page_shows_featured_album_photos_block(): void
    {
        $album = Album::factory()->create(['is_published' => true, 'title' => 'Галерея примеров']);

        $media1 = Media::factory()->create(['file_path' => 'albums/photo-1.jpg']);
        $media2 = Media::factory()->create(['file_path' => 'albums/photo-2.jpg']);

        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media1->id, 'caption' => 'Фото один', 'sort_order' => 1]);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media2->id, 'sort_order' => 2]);

        $this->classic->albums()->attach($album->id);
        $this->classic->update(['show_album_photos' => true, 'featured_album_id' => $album->id]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertSee('Галерея примеров');
        $response->assertSee(route('media.lightbox', ['media' => $media1->id]), false);
        $response->assertSee(route('media.display', ['media' => $media1->id]), false);
        $response->assertSee(route('media.lightbox', ['media' => $media2->id]), false);
        $response->assertSee('data-caption="Фото один"', false);
        $response->assertSee('lightboxCaption', false);
        $response->assertDontSee(route('portfolio.show', $album->slug), false);
    }

    public function test_service_page_shows_remaining_albums_as_cards_before_photos(): void
    {
        $featured = Album::factory()->create(['is_published' => true, 'title' => 'Альбом-блок']);
        $other = Album::factory()->create(['is_published' => true, 'title' => 'Обычный пример']);

        $media = Media::factory()->create(['file_path' => 'albums/featured.jpg']);
        Photo::factory()->create(['album_id' => $featured->id, 'media_id' => $media->id]);

        $this->classic->albums()->attach([$featured->id, $other->id]);
        $this->classic->update(['show_album_photos' => true, 'featured_album_id' => $featured->id]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertSeeInOrder(['Примеры работ', 'Обычный пример', 'Альбом-блок']);
        $response->assertSee(route('portfolio.show', $other->slug), false);
        $response->assertDontSee(route('portfolio.show', $featured->slug), false);
    }

    public function test_service_page_hides_featured_album_photos_when_toggle_off(): void
    {
        $album = Album::factory()->create(['is_published' => true, 'title' => 'Обычный пример']);

        $media = Media::factory()->create(['file_path' => 'albums/hidden.jpg']);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);

        $this->classic->albums()->attach($album->id);
        $this->classic->update(['show_album_photos' => false, 'featured_album_id' => $album->id]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertDontSee(route('media.lightbox', ['media' => $media->id]), false);
        $response->assertSee('Обычный пример');
        $response->assertSee(route('portfolio.show', $album->slug), false);
    }

    public function test_service_page_does_not_show_unpublished_featured_album(): void
    {
        $album = Album::factory()->create(['is_published' => false, 'title' => 'Черновик']);

        $media = Media::factory()->create(['file_path' => 'albums/draft.jpg']);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);

        $this->classic->albums()->attach($album->id);
        $this->classic->update(['show_album_photos' => true, 'featured_album_id' => $album->id]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertDontSee(route('media.lightbox', ['media' => $media->id]), false);
        $response->assertDontSee('Черновик');
    }

    public function test_three_level_parent_page_hides_grandchild_services(): void
    {
        $classicCategory = Category::factory()->create([
            'type' => 'service',
            'slug' => 'klassika',
            'parent_id' => $this->schools->id,
            'is_published' => true,
            'name' => 'Классика',
        ]);

        Service::factory()->create([
            'category_id' => $classicCategory->id,
            'slug' => 'standart',
            'is_published' => true,
            'title' => 'Стандарт',
        ]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertStatus(200);
        $response->assertDontSee('Стандарт');
    }

    public function test_three_level_category_page_renders_full_breadcrumb(): void
    {
        $classicCategory = Category::factory()->create([
            'type' => 'service',
            'slug' => 'klassika',
            'parent_id' => $this->schools->id,
            'is_published' => true,
            'name' => 'Классика',
        ]);

        Service::factory()->create([
            'category_id' => $classicCategory->id,
            'slug' => 'standart',
            'is_published' => true,
            'title' => 'Стандарт',
        ]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Главная', 'Услуги', 'Выпускные альбомы', 'Для школ', 'Классика']);
        $response->assertSee('Стандарт');
    }

    public function test_three_level_service_renders_full_breadcrumb(): void
    {
        $classicCategory = Category::factory()->create([
            'type' => 'service',
            'slug' => 'klassika',
            'parent_id' => $this->schools->id,
            'is_published' => true,
            'name' => 'Классика',
        ]);

        Service::factory()->create([
            'category_id' => $classicCategory->id,
            'slug' => 'standart',
            'is_published' => true,
            'title' => 'Стандарт',
        ]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika/standart');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Главная', 'Услуги', 'Выпускные альбомы', 'Для школ', 'Классика', 'Стандарт']);
        $response->assertSee('Стандарт');
    }

    public function test_service_page_renders_full_breadcrumb(): void
    {
        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertSeeInOrder(['Главная', 'Услуги', 'Выпускные альбомы', 'Для школ', 'Классика']);
        $response->assertSee('/services/vypusknye-albomy');
        $response->assertSee('/services/vypusknye-albomy/dlya-shkol');
        $response->assertSeeHtml('aria-current="page">Классика</span>');
    }

    public function test_category_page_shows_cover_image(): void
    {
        $media = Media::factory()->create([
            'disk' => 'public',
            'file_path' => 'covers/album-cover.jpg',
        ]);

        $this->albums->update(['cover_media_id' => $media->id]);

        $response = $this->get('/services/vypusknye-albomy');

        $response->assertSee(route('media.display', ['media' => $media->id]), false);
        $response->assertSee('alt="Выпускные альбомы"', false);
    }

    public function test_category_page_shows_child_categories(): void
    {
        $response = $this->get('/services/vypusknye-albomy');

        $response->assertSee('Разделы');
        $response->assertSee('Для школ');
        $response->assertSee('/services/vypusknye-albomy/dlya-shkol');
        $response->assertSee('от 8 000', false);
    }

    public function test_category_page_shows_attached_videos(): void
    {
        $video = Video::factory()->create([
            'title' => 'Видео о выпускных',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_active' => true,
        ]);

        $this->schools->videos()->attach($video->id);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertStatus(200);
        $response->assertSee('Видео о выпускных');
        $response->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_category_page_shows_attached_items(): void
    {
        $item = ServiceItem::factory()->create([
            'label' => 'Дизайнерская вёрстка',
            'is_included' => true,
        ]);

        $this->schools->items()->attach($item->id, ['is_included' => true, 'sort_order' => 0]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertStatus(200);
        $response->assertSee('Что входит');
        $response->assertSee('Дизайнерская вёрстка');
    }

    public function test_category_page_shows_service_cards(): void
    {
        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertSee('Варианты оформления');
        $response->assertSee('Классика');
        $response->assertSee('Классический альбом');
        $response->assertSee('от 15 000', false);
        $response->assertSee('Подробнее');
        $response->assertSee('/services/vypusknye-albomy/dlya-shkol/klassika');
    }

    public function test_category_page_renders_price_and_note(): void
    {
        Category::factory()->create([
            'type' => 'service',
            'slug' => 'semejnaya-semka',
            'name' => 'Семейная фотосессия',
            'parent_id' => null,
            'is_published' => true,
            'description' => '<p>Съёмка для всей семьи</p>',
            'price_from' => 7000,
            'price_note' => 'до 1 часа съёмки',
        ]);

        $response = $this->get('/services/semejnaya-semka');

        $response->assertSee('от 7 000 ₽', false);
        $response->assertSee('до 1 часа съёмки');
    }

    public function test_category_page_shows_inquiry_cta_form(): void
    {
        $response = $this->get('/services/vypusknye-albomy');

        $response->assertSee('Оставить заявку');
        $response->assertSee(route('inquiry.store'));
        $response->assertSee('Соглашаюсь на обработку персональных данных');
        $response->assertSee('name="phone"', false);
    }

    public function test_wrong_chain_returns_404(): void
    {
        $response = $this->get('/services/vypusknye-albomy/wrong/klassika');

        $response->assertNotFound();
    }

    public function test_unknown_category_returns_404(): void
    {
        $response = $this->get('/services/ne-sushchestvuet');

        $response->assertNotFound();
    }

    public function test_unpublished_category_returns_404(): void
    {
        Category::factory()->create([
            'type' => 'service',
            'slug' => 'hidden',
            'parent_id' => null,
            'is_published' => false,
        ]);

        $response = $this->get('/services/hidden');

        $response->assertNotFound();
    }

    public function test_unpublished_nested_category_returns_404(): void
    {
        Category::factory()->create([
            'type' => 'service',
            'slug' => 'hidden',
            'parent_id' => $this->albums->id,
            'is_published' => false,
        ]);

        $response = $this->get('/services/vypusknye-albomy/hidden');

        $response->assertNotFound();
    }

    public function test_unpublished_service_returns_404(): void
    {
        Service::factory()->create([
            'slug' => 'hidden-service',
            'category_id' => $this->schools->id,
            'is_published' => false,
        ]);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/hidden-service');

        $response->assertNotFound();
    }

    public function test_service_without_category_still_resolves(): void
    {
        $service = Service::factory()->create([
            'slug' => 'individualnaya-semka',
            'category_id' => null,
            'is_published' => true,
            'title' => 'Индивидуальная съёмка',
        ]);

        $response = $this->get('/services/individualnaya-semka');

        $response->assertStatus(200);
        $response->assertSee('Индивидуальная съёмка');
    }

    public function test_url_generation_uses_full_hierarchical_path(): void
    {
        $this->assertSame(
            url('/services/vypusknye-albomy/dlya-shkol'),
            route('services.show', $this->schools->catalogPath())
        );
        $this->assertSame(
            url('/services/vypusknye-albomy/dlya-shkol/klassika'),
            route('services.show', $this->classic->catalogPath())
        );
    }

    public function test_category_page_uses_seo_from_page_when_category_seo_empty(): void
    {
        $page = Page::factory()->create([
            'slug' => 'services',
            'title' => 'Наши услуги',
            'seo_title' => 'Каталог услуг',
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get('/services/vypusknye-albomy');

        $response->assertStatus(200);
    }

    public function test_service_page_shows_cta_button_when_configured(): void
    {
        $ctaAlbum = Album::factory()->create(['is_published' => true, 'title' => 'Варианты обложек']);
        $this->classic->update(['cta_album_id' => $ctaAlbum->id, 'cta_button_text' => 'Посмотреть варианты обложек']);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertSee('Посмотреть варианты обложек');
        $response->assertSee(route('portfolio.show', $ctaAlbum->slug), false);
    }

    public function test_service_page_hides_cta_button_when_not_configured(): void
    {
        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertDontSee('Посмотреть варианты обложек');
    }

    public function test_service_page_hides_cta_button_when_only_text_set(): void
    {
        $this->classic->update(['cta_album_id' => null, 'cta_button_text' => 'Некий текст']);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol/klassika');

        $response->assertStatus(200);
        $response->assertDontSee('Некий текст');
    }

    public function test_category_page_shows_cta_button_when_configured(): void
    {
        $ctaAlbum = Album::factory()->create(['is_published' => true, 'title' => 'Обложки школ']);
        $this->schools->update(['cta_album_id' => $ctaAlbum->id, 'cta_button_text' => 'Посмотреть варианты']);

        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertStatus(200);
        $response->assertSee('Посмотреть варианты');
        $response->assertSee(route('portfolio.show', $ctaAlbum->slug), false);
    }

    public function test_category_page_hides_cta_button_when_not_configured(): void
    {
        $response = $this->get('/services/vypusknye-albomy/dlya-shkol');

        $response->assertStatus(200);
        $response->assertDontSee('Посмотреть варианты');
    }
}
