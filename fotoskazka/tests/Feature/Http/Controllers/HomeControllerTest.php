<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Album;
use App\Models\Category;
use App\Models\FaqItem;
use App\Models\Media;
use App\Models\NotificationSetting;
use App\Models\Page;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Service;
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

    public function test_home_page_renders_ar_teaser(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Скоро в&nbsp;Фотосказке&nbsp;—', false)
            ->assertSee('оживающие фотографии')
            ->assertSee('ar-teaser')
            ->assertSee('images/ar-teaser.jpg');
    }

    public function test_ar_teaser_hidden_when_disabled(): void
    {
        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => false,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('ar-teaser')
            ->assertDontSee('Скоро в&nbsp;Фотосказке&nbsp;—', false);
    }

    public function test_ar_teaser_title_and_subtitle_from_database(): void
    {
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

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Тестовый заголовок AR')
            ->assertSee('Тестовый акцент AR')
            ->assertSee('Тестовое описание AR')
            ->assertSee('Тестовый футер AR');
    }

    public function test_ar_teaser_accent_and_footer_defaults_when_null(): void
    {
        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_title' => null,
            'ar_teaser_accent' => null,
            'ar_teaser_subtitle' => null,
            'ar_teaser_footer' => null,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('оживающие фотографии')
            ->assertSee('Следите за&nbsp;новостями&nbsp;— подробности скоро появятся на&nbsp;сайте.', false);
    }

    public function test_ar_teaser_image_from_selected_media(): void
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
            ->assertSee('/media/'.$media->getKey().'/', false)
            ->assertDontSee('images/ar-teaser.jpg');
    }

    public function test_ar_teaser_without_media_shows_fallback(): void
    {
        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_enabled' => true,
            'ar_teaser_media_id' => null,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('ar-teaser')
            ->assertSee('images/ar-teaser.jpg');
    }

    public function test_ar_teaser_default_values_when_fields_null(): void
    {
        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_title' => null,
            'ar_teaser_subtitle' => null,
            'ar_teaser_media_id' => null,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Скоро в&nbsp;Фотосказке&nbsp;—', false)
            ->assertSee('оживающие фотографии')
            ->assertSee('images/ar-teaser.jpg');
    }

    public function test_ar_teaser_page_saved_clears_cache(): void
    {
        Page::factory()->create([
            'slug' => 'home',
            'is_published' => true,
            'ar_teaser_title' => 'Original Title',
        ]);

        Cache::flush();
        $this->get('/');

        $page = Page::where('slug', 'home')->first();
        $page->ar_teaser_title = 'Updated Title';
        $page->save();

        Cache::flush();
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Updated Title')
            ->assertDontSee('Original Title');
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

    public function test_home_page_shows_service_categories(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'name' => 'Выпускные альбомы',
        ]);

        $response = $this->get('/');

        $response->assertSee('Выпускные альбомы');
    }

    public function test_home_page_hides_unpublished_categories(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => false,
            'name' => 'Скрытая категория',
        ]);

        $response = $this->get('/');

        $response->assertDontSee('Скрытая категория');
    }

    public function test_home_page_hides_child_categories(): void
    {
        $parent = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
            'name' => 'Родитель',
        ]);

        $child = Category::factory()->create([
            'type' => 'service',
            'parent_id' => $parent->id,
            'is_published' => true,
            'name' => 'Дочерняя',
        ]);

        $response = $this->get('/');

        $response->assertSee('Родитель');
        $response->assertDontSee('Дочерняя');
    }

    public function test_home_page_shows_all_services_link(): void
    {
        Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'is_published' => true,
        ]);

        $response = $this->get('/');

        $response->assertSee('Все услуги');
        $response->assertSee(route('services.index'), false);
    }

    public function test_home_page_shows_featured_portfolio(): void
    {
        $album = Album::factory()->create([
            'type' => 'portfolio',
            'is_featured' => true,
            'is_published' => true,
            'title' => 'Избранный проект',
        ]);

        $response = $this->get('/');

        $response->assertSee('Избранный проект');
    }

    public function test_home_page_shows_testimonials(): void
    {
        $testimonial = Testimonial::factory()->create([
            'is_published' => true,
            'client_name' => 'Анна С.',
            'content' => 'Отличный фотограф!',
        ]);

        $response = $this->get('/');

        $response->assertSee('Анна С.');
        $response->assertSee('Отличный фотограф!');
    }

    public function test_home_page_shows_latest_posts(): void
    {
        $post = Post::factory()->create([
            'is_published' => true,
            'published_at' => now()->subDay(),
            'title' => 'Как подготовиться к съёмке',
            'excerpt' => 'Полезные советы',
        ]);

        $response = $this->get('/');

        $response->assertSee('Как подготовиться к съёмке');
    }

    public function test_home_page_shows_faq(): void
    {
        $faq = FaqItem::create([
            'is_active' => true,
            'question' => 'Сколько стоят услуги?',
            'answer' => 'Всё индивидуально',
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertSee('Сколько стоят услуги?');
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
        $response->assertSee(route('media.display', ['media' => $media->getKey()]), false);
        $response->assertSee('data-original="'.e($media->getUrl()).'"', false);
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
        $response->assertSee($media->getUrl(), false);
        $response->assertDontSee('data-original="'.$media->getUrl().'"', false);
    }

    public function test_home_page_shows_inquiry_form(): void
    {
        $response = $this->get('/');

        $response->assertSee('Оставить заявку');
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
}
