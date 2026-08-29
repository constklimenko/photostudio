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

    public function test_home_page_shows_services(): void
    {
        $service = Service::factory()->create([
            'is_published' => true,
            'title' => 'Свадебная съёмка',
        ]);

        $response = $this->get('/');

        $response->assertSee('Свадебная съёмка');
    }

    public function test_home_page_hides_unpublished_services(): void
    {
        $service = Service::factory()->create([
            'is_published' => false,
            'title' => 'Скрытая услуга',
        ]);

        $response = $this->get('/');

        $response->assertDontSee('Скрытая услуга');
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
