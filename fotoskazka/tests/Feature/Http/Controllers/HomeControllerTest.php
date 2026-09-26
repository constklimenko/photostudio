<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\FaqItem;
use App\Models\Media;
use App\Models\NotificationSetting;
use App\Models\Page;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Service;
use App\Models\SocialLink;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NotificationSetting::create([
            'title' => 'Test',
            'email_enabled' => false,
            'email_recipients' => [],
            'telegram_enabled' => false,
        ]);
    }

    private function enablePortfolioSection(bool $showOnHome = true): Page
    {
        $page = Page::factory()->create([
            'slug' => 'portfolio',
            'title' => 'Портфолио',
            'is_published' => true,
            'show_on_home' => $showOnHome,
        ]);

        Cache::flush();

        return $page;
    }

    public function test_home_page_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_home_page_shows_default_title(): void
    {
        $response = $this->get('/');

        $response->assertSee('ФОТОСКАЗКА УФА');
    }

    public function test_home_page_shows_page_title_from_database(): void
    {
        Page::factory()->create([
            'slug' => 'home',
            'title' => 'Фотосказка — Главная',
            'is_published' => true,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertSee('Фотосказка — Главная');
    }

    public function test_home_page_contains_hero_and_social_links_only(): void
    {
        Testimonial::factory()->create([
            'is_published' => true,
            'client_name' => 'Анна С.',
            'content' => 'Отличный фотограф!',
        ]);

        Post::factory()->create([
            'is_published' => true,
            'published_at' => now()->subDay(),
            'title' => 'Как подготовиться к съёмке',
        ]);

        FaqItem::create([
            'is_active' => true,
            'question' => 'Сколько стоят услуги?',
            'answer' => 'Всё индивидуально',
            'sort_order' => 1,
        ]);

        Page::factory()->create([
            'slug' => 'home',
            'about_studio_text' => '<p>Мы — студия семейной фотографии.</p>',
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="hero-block"', false)
            ->assertDontSee('Анна С.')
            ->assertDontSee('Как подготовиться к съёмке')
            ->assertDontSee('Сколько стоят услуги?')
            ->assertDontSee('Мы — студия семейной фотографии')
            ->assertDontSee('Наши услуги');
    }

    public function test_home_page_shows_social_links(): void
    {
        SocialLink::create([
            'name' => 'Telegram',
            'url' => 'https://t.me/fotoskazka',
            'icon' => 'telegram',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('https://t.me/fotoskazka')
            ->assertSee('Telegram');
    }

    public function test_home_page_shows_inquiry_button_in_header(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-open-modal="inquiry"', false)
            ->assertSee('Оставить заявку');
    }

    public function test_home_page_does_not_render_ar_teaser(): void
    {
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

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('ar-teaser')
            ->assertDontSee('Скоро в&nbsp;Фотосказке&nbsp;—', false)
            ->assertDontSee('оживающие фотографии')
            ->assertDontSee('images/ar-teaser.jpg');
    }

    public function test_home_page_does_not_render_shooting_works_block(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'shooting/card.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $album = Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'cover_media_id' => $media->getKey(),
            'title' => 'За кадром',
            'description' => 'Как проходила съёмка',
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Фото со съёмок')
            ->assertDontSee('За кадром')
            ->assertDontSee('Как проходила съёмка')
            ->assertDontSee(route('portfolio.show', $album->slug), false);
    }

    public function test_home_page_shows_featured_works_when_portfolio_page_enabled(): void
    {
        $this->enablePortfolioSection();

        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный проект',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-home-block="featured-works"', false)
            ->assertSee('Портфолио')
            ->assertSee('Избранный проект')
            ->assertSee(route('portfolio.show', $album->slug), false);
    }

    public function test_home_page_hides_featured_works_when_portfolio_page_disabled(): void
    {
        $this->enablePortfolioSection(false);

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный проект',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('data-home-block="featured-works"', false)
            ->assertDontSee('Избранный проект');
    }

    public function test_home_page_hides_featured_works_without_portfolio_page(): void
    {
        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный проект',
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('data-home-block="featured-works"', false)
            ->assertDontSee('Избранный проект');
    }

    public function test_home_page_hides_featured_works_without_featured_albums(): void
    {
        $this->enablePortfolioSection();

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => false,
            'is_published' => true,
            'title' => 'Обычный проект',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('data-home-block="featured-works"', false)
            ->assertDontSee('Обычный проект');
    }

    public function test_behind_the_scenes_albums_not_shown_in_featured_works(): void
    {
        $this->enablePortfolioSection();

        Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный шедевр',
        ]);

        Album::factory()->create([
            'type' => 'behind_the_scenes',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'За кадром съёмки',
        ]);

        $response = $this->get('/');
        $featured = $this->featuredWorksSection($response->getContent());

        $this->assertNotSame('', $featured);
        $this->assertStringContainsString('Избранный шедевр', $featured);
        $this->assertStringNotContainsString('За кадром съёмки', $featured);
    }

    public function test_home_hero_loads_cached_version_first_then_original(): void
    {
        Storage::fake('public');
        Storage::fake('thumbnails');

        $image = imagecreatetruecolor(1200, 800);
        ob_start();
        imagejpeg($image, quality: 85);
        imagedestroy($image);

        Storage::disk('public')->put('hero/photo.jpg', (string) ob_get_clean());

        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'hero/photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $album = Album::factory()->create([
            'type' => 'homepage',
            'is_published' => true,
            'title' => 'Главная',
        ]);

        Photo::factory()->create([
            'album_id' => $album->getKey(),
            'media_id' => $media->getKey(),
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);

        $html = $response->getContent();

        preg_match('/<section[^>]*id="hero-block".*?<\/section>/s', $html, $matches);

        $hero = $matches[0] ?? '';

        $this->assertNotSame('', $hero);
        $this->assertStringContainsString(route('media.display', ['media' => $media->getKey(), 'v' => 'webp']), $hero);
        $this->assertStringContainsString('data-original="'.e($media->getUrl()).'"', $hero);
        $this->assertStringContainsString('fetchpriority="high"', $hero);
        $this->assertStringNotContainsString('loading=', $hero);
    }

    public function test_home_hero_without_cache_falls_back_to_original(): void
    {
        Storage::fake('public');

        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'hero/photo.jpg',
            'mime_type' => 'application/octet-stream',
        ]);

        $album = Album::factory()->create([
            'type' => 'homepage',
            'is_published' => true,
            'title' => 'Главная',
        ]);

        Photo::factory()->create([
            'album_id' => $album->getKey(),
            'media_id' => $media->getKey(),
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);

        preg_match('/<section[^>]*id="hero-block".*?<\/section>/s', $response->getContent(), $matches);

        $hero = $matches[0] ?? '';

        $this->assertNotSame('', $hero);
        $this->assertStringContainsString($media->getUrl(), $hero);
        $this->assertStringNotContainsString('data-original="'.$media->getUrl().'"', $hero);
        $this->assertStringContainsString('fetchpriority="high"', $hero);
    }

    public function test_home_hero_uses_first_photo_of_homepage_album(): void
    {
        $album = Album::factory()->create([
            'type' => 'homepage',
            'is_published' => true,
            'title' => 'Главная',
        ]);

        $media = collect(['second', 'first', 'third'])->map(fn (string $name) => Media::query()->create([
            'disk' => 'public',
            'file_path' => 'hero/'.$name.'.jpg',
            'mime_type' => 'application/octet-stream',
        ]));

        foreach ([[1, $media[1]], [2, $media[0]], [3, $media[2]]] as [$sortOrder, $item]) {
            Photo::factory()->create([
                'album_id' => $album->getKey(),
                'media_id' => $item->getKey(),
                'sort_order' => $sortOrder,
            ]);
        }

        $response = $this->get('/');

        $response->assertStatus(200);

        preg_match('/<section[^>]*id="hero-block".*?<\/section>/s', $response->getContent(), $matches);

        $hero = $matches[0] ?? '';

        $this->assertNotSame('', $hero);
        $this->assertStringContainsString($media[1]->getUrl(), $hero);
        $this->assertStringNotContainsString($media[0]->getUrl(), $hero);
        $this->assertStringNotContainsString($media[2]->getUrl(), $hero);
    }

    public function test_store_inquiry_creates_inquiry(): void
    {
        $response = $this->post(route('inquiry.store'), [
            'name' => 'Иван Иванов',
            'phone' => '+7-123-456-78-90',
            'email' => 'ivan@example.com',
            'message' => 'Хочу заказать съёмку',
            'agreed_to_terms' => true,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Иван Иванов',
            'phone' => '+7-123-456-78-90',
            'email' => 'ivan@example.com',
            'message' => 'Хочу заказать съёмку',
            'status' => 'new',
        ]);
    }

    public function test_store_inquiry_validates_required_fields(): void
    {
        $response = $this->post(route('inquiry.store'), []);

        $response->assertSessionHasErrors(['name', 'phone', 'email', 'agreed_to_terms']);
    }

    public function test_store_inquiry_validates_email_format(): void
    {
        $response = $this->post(route('inquiry.store'), [
            'name' => 'Иван',
            'phone' => '+7-123-456-78-90',
            'email' => 'invalid-email',
            'agreed_to_terms' => true,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_inquiry_validates_service_exists(): void
    {
        $response = $this->post(route('inquiry.store'), [
            'name' => 'Иван',
            'phone' => '+7-123-456-78-90',
            'email' => 'ivan@example.com',
            'service_id' => 9999,
            'agreed_to_terms' => true,
        ]);

        $response->assertSessionHasErrors(['service_id']);
    }

    public function test_store_inquiry_accepts_valid_service(): void
    {
        $service = Service::factory()->create();

        $response = $this->post(route('inquiry.store'), [
            'name' => 'Иван',
            'phone' => '+7-123-456-78-90',
            'email' => 'ivan@example.com',
            'service_id' => $service->id,
            'agreed_to_terms' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inquiries', [
            'service_id' => $service->id,
        ]);
    }

    public function test_store_inquiry_with_shooting_date(): void
    {
        $response = $this->post(route('inquiry.store'), [
            'name' => 'Иван',
            'phone' => '+7-123-456-78-90',
            'email' => 'ivan@example.com',
            'shooting_date' => now()->addDays(30)->format('Y-m-d'),
            'agreed_to_terms' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inquiries', [
            'email' => 'ivan@example.com',
        ]);
    }

    public function test_store_inquiry_rejects_past_shooting_date(): void
    {
        $response = $this->post(route('inquiry.store'), [
            'name' => 'Иван',
            'phone' => '+7-123-456-78-90',
            'email' => 'ivan@example.com',
            'shooting_date' => now()->subDays(1)->format('Y-m-d'),
            'agreed_to_terms' => true,
        ]);

        $response->assertSessionHasErrors(['shooting_date']);
    }

    public function test_store_inquiry_requires_terms_agreement(): void
    {
        $response = $this->post(route('inquiry.store'), [
            'name' => 'Иван',
            'phone' => '+7-123-456-78-90',
            'email' => 'ivan@example.com',
            'agreed_to_terms' => false,
        ]);

        $response->assertSessionHasErrors(['agreed_to_terms']);
    }

    private function featuredWorksSection(string $html): string
    {
        preg_match('/<section[^>]*data-home-block="featured-works".*?<\/section>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
