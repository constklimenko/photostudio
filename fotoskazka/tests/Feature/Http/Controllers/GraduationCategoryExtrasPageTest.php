<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GraduationCategoryExtrasPageTest extends TestCase
{
    use RefreshDatabase;

    private function createGraduationTree(bool $isGraduation = true): Category
    {
        $root = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'is_graduation_albums' => $isGraduation,
            'name' => 'Выпускные альбомы',
        ]);

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

        $service->category()->associate($child)->save();

        return $root;
    }

    public function test_graduation_category_page_renders_ar_teaser(): void
    {
        $root = $this->createGraduationTree();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('Скоро в&nbsp;Фотосказке&nbsp;—', false)
            ->assertSee('оживающие фотографии')
            ->assertSee('ar-teaser')
            ->assertSee('images/ar-teaser.jpg');
    }

    public function test_graduation_category_page_renders_ar_teaser_texts_from_home_page(): void
    {
        $root = $this->createGraduationTree();

        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => true,
            'ar_teaser_title' => 'Тестовый заголовок AR',
            'ar_teaser_subtitle' => 'Тестовое описание AR',
            'ar_teaser_accent' => 'Тестовый акцент AR',
            'ar_teaser_footer' => 'Тестовый футер AR',
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('Тестовый заголовок AR')
            ->assertSee('Тестовый акцент AR')
            ->assertSee('Тестовое описание AR')
            ->assertSee('Тестовый футер AR');
    }

    public function test_graduation_category_page_ar_teaser_falls_back_to_component_defaults(): void
    {
        $root = $this->createGraduationTree();

        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => true,
            'ar_teaser_title' => null,
            'ar_teaser_accent' => null,
            'ar_teaser_subtitle' => null,
            'ar_teaser_footer' => null,
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('Скоро в&nbsp;Фотосказке&nbsp;—', false)
            ->assertSee('оживающие фотографии')
            ->assertSee('Следите за&nbsp;новостями&nbsp;— подробности скоро появятся на&nbsp;сайте.', false);
    }

    public function test_graduation_category_page_hides_ar_teaser_when_disabled(): void
    {
        $root = $this->createGraduationTree();

        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => false,
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertDontSee('ar-teaser')
            ->assertDontSee('Скоро в&nbsp;Фотосказке&nbsp;—', false)
            ->assertDontSee('оживающие фотографии');
    }

    public function test_graduation_category_page_renders_ar_teaser_media(): void
    {
        $root = $this->createGraduationTree();

        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'ar/custom-photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => true,
            'ar_teaser_media_id' => $media->id,
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('/media/'.$media->getKey().'/', false)
            ->assertDontSee('images/ar-teaser.jpg');
    }

    public function test_graduation_category_page_ar_teaser_uses_fallback_image_without_media(): void
    {
        $root = $this->createGraduationTree();

        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => true,
            'ar_teaser_media_id' => null,
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('ar-teaser')
            ->assertSee('images/ar-teaser.jpg');
    }

    public function test_graduation_category_page_ar_teaser_reflects_saved_settings(): void
    {
        $root = $this->createGraduationTree();

        $page = Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => true,
            'ar_teaser_title' => 'Original Title',
        ]);

        Cache::flush();

        $this->get(route('services.show', $root->catalogPath()))
            ->assertOk()
            ->assertSee('Original Title');

        $page->update(['ar_teaser_title' => 'Updated Title']);

        $this->get(route('services.show', $root->catalogPath()))
            ->assertOk()
            ->assertSee('Updated Title')
            ->assertDontSee('Original Title');
    }

    public function test_graduation_category_page_renders_shooting_works_block(): void
    {
        $root = $this->createGraduationTree();

        $album = Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'За кадром выпускного',
            'description' => 'Как проходила съёмка',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('Фото со съёмок')
            ->assertSee('За кадром выпускного')
            ->assertSee('Как проходила съёмка')
            ->assertSee(route('portfolio.show', $album->slug), false);
    }

    public function test_graduation_category_page_hides_unpublished_shooting_albums(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => false,
            'title' => 'Неопубликованный закадровый',
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertDontSee('Фото со съёмок')
            ->assertDontSee('Неопубликованный закадровый');
    }

    public function test_graduation_category_page_hides_unfeatured_shooting_albums(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => false,
            'is_published' => true,
            'title' => 'Неизбранный закадровый',
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertDontSee('Фото со съёмок')
            ->assertDontSee('Неизбранный закадровый');
    }

    public function test_graduation_category_page_ignores_other_album_types_in_shooting_block(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Обычное портфолио',
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertDontSee('Фото со съёмок');
    }

    public function test_graduation_category_page_renders_shooting_work_cover(): void
    {
        $root = $this->createGraduationTree();

        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'shooting/card.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'cover_media_id' => $media->getKey(),
            'title' => 'За кадром',
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('/media/'.$media->getKey().'/', false);
    }

    public function test_graduation_category_page_orders_shooting_works_by_sort_order(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Первая съёмка',
            'sort_order' => 1,
        ]);

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Вторая съёмка',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));
        $html = $response->getContent();

        preg_match('/<section[^>]*>\s*<div[^>]*>\s*<h2[^>]*>\s*Фото со съёмок\s*<\/h2>.*?<\/section>/su', $html, $matches);
        $section = $matches[0] ?? '';

        $this->assertNotSame('', $section);
        $this->assertGreaterThan(
            strpos($section, 'Первая съёмка'),
            strpos($section, 'Вторая съёмка')
        );
    }

    public function test_graduation_category_page_shows_shooting_block_texts_from_shooting_page(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'За кадром',
        ]);

        Page::factory()->create([
            'slug' => 'shooting',
            'title' => 'Название блока из страницы',
            'subtitle' => 'Подзаголовок блока из страницы',
            'home_title' => 'Кастомное название',
            'home_subtitle' => 'Кастомный подзаголовок',
            'home_content' => '<p>Описание блока</p>',
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('Кастомное название')
            ->assertSee('Кастомный подзаголовок')
            ->assertSee('Описание блока');
    }

    public function test_graduation_category_page_shooting_block_falls_back_to_defaults_without_page(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'За кадром',
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertSee('Фото со съёмок')
            ->assertSee('Загляните на съёмочную площадку');
    }

    public function test_graduation_category_page_places_blocks_in_required_order(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'За кадром',
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Стоимость альбомов',
            'оживающие фотографии',
            'Фото со съёмок',
        ]);
    }

    public function test_category_page_without_graduation_flag_keeps_standard_layout(): void
    {
        $root = $this->createGraduationTree(isGraduation: false);

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'За кадром',
        ]);

        $response = $this->get(route('services.show', $root->catalogPath()));

        $response->assertOk()
            ->assertDontSee('ar-teaser')
            ->assertDontSee('оживающие фотографии')
            ->assertDontSee('Фото со съёмок')
            ->assertDontSee('За кадром')
            ->assertDontSee('Стоимость альбомов');
    }

    public function test_graduation_category_page_shooting_works_avoid_n_plus_one(): void
    {
        $root = $this->createGraduationTree();

        Album::factory()->count(25)->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
        ])->each(function (Album $album) {
            $album->update([
                'cover_media_id' => Media::query()->create([
                    'disk' => 'public',
                    'file_path' => "shooting/{$album->getKey()}.jpg",
                    'mime_type' => 'image/jpeg',
                ])->getKey(),
            ]);
        });

        \DB::enableQueryLog();
        $response = $this->get(route('services.show', $root->catalogPath()));
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $response->assertOk();
        $this->assertLessThanOrEqual(30, $queryCount, "Expected ≤30 queries for 25 shooting albums, got {$queryCount}. Possible N+1 issue.");
    }
}
