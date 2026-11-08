<?php

namespace Tests\Feature\Components;

use App\Models\Category;
use App\Models\FaqItem;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\Video;
use App\Services\HomeContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Блоки главной, которые сейчас не выводятся на `/`, сохранены
 * в `x-site.home.*`. Тесты гарантируют, что они продолжают работать
 * и могут быть включены обратно без переписывания разметки.
 */
class HomeBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_block_renders_cards_with_prices(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'parent_id' => null,
            'name' => 'Выпускные альбомы',
            'is_published' => true,
            'price_from' => 15000,
        ]);

        $html = $this->render('components.site.home.services', [
            'categories' => app(HomeContentService::class)->servicesGrid(),
            'title' => 'Наши услуги',
            'subtitle' => 'Выберите формат',
            'content' => null,
        ]);

        $this->assertStringContainsString('Наши услуги', $html);
        $this->assertStringContainsString('Выпускные альбомы', $html);
        $this->assertStringContainsString('от 15 000', $html);
    }

    public function test_services_block_hidden_without_categories(): void
    {
        $html = $this->render('components.site.home.services', [
            'categories' => app(HomeContentService::class)->servicesGrid(),
        ]);

        $this->assertSame('', trim($html));
    }

    public function test_videos_block_renders_active_home_videos(): void
    {
        Video::factory()->create([
            'title' => 'Наш ролик',
            'url' => 'https://youtu.be/abc123',
            'type' => 'horizontal',
            'is_active' => true,
            'show_on_home' => true,
            'sort_order' => 1,
        ]);

        $html = $this->render('components.site.home.videos', [
            'videos' => app(HomeContentService::class)->videos(),
        ]);

        $this->assertStringContainsString('Видеогалерея', $html);
        $this->assertStringContainsString('Наш ролик', $html);
        $this->assertStringContainsString('youtube.com/embed/abc123', $html);
    }

    public function test_testimonials_block_renders_published_entries(): void
    {
        Testimonial::factory()->create([
            'is_published' => true,
            'client_name' => 'Мария К.',
            'content' => 'Свадьба получилась волшебной',
        ]);

        $html = $this->render('components.site.home.testimonials', [
            'testimonials' => app(HomeContentService::class)->testimonials(),
        ]);

        $this->assertStringContainsString('Отзывы', $html);
        $this->assertStringContainsString('Мария К.', $html);
        $this->assertStringContainsString('Свадьба получилась волшебной', $html);
    }

    public function test_blog_block_renders_latest_published_posts(): void
    {
        Post::factory()->create([
            'is_published' => true,
            'published_at' => now()->subDay(),
            'title' => 'Позирование без стресса',
        ]);

        $html = $this->render('components.site.home.blog', [
            'posts' => app(HomeContentService::class)->latestPosts(),
        ]);

        $this->assertStringContainsString('Последние статьи', $html);
        $this->assertStringContainsString('Позирование без стресса', $html);
    }

    public function test_faq_block_renders_active_items(): void
    {
        FaqItem::query()->create([
            'question' => 'Сколько длится съёмка?',
            'answer' => 'От двух часов',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $html = $this->render('components.site.home.faq', [
            'items' => app(HomeContentService::class)->faqItems(),
        ]);

        $this->assertStringContainsString('Часто задаваемые вопросы', $html);
        $this->assertStringContainsString('Сколько длится съёмка?', $html);
        $this->assertStringContainsString('От двух часов', $html);
    }

    public function test_about_studio_block_renders_html_text(): void
    {
        $html = $this->render('components.site.home.about-studio', [
            'text' => '<p>Мы снимаем <strong>честные</strong> истории</p>',
        ]);

        $this->assertStringContainsString('О студии', $html);
        $this->assertStringContainsString('честные', $html);
    }

    public function test_about_studio_block_hidden_without_text(): void
    {
        $html = $this->render('components.site.home.about-studio', ['text' => null]);

        $this->assertSame('', trim($html));
    }

    public function test_inquiry_block_renders_form_with_services(): void
    {
        Service::factory()->create(['title' => 'Семейная фотосессия', 'is_published' => true]);

        $html = $this->render('components.site.home.inquiry', [
            'services' => app(HomeContentService::class)->inquiryServices(),
        ]);

        $this->assertStringContainsString('id="inquiry-form"', $html);
        $this->assertStringContainsString(route('inquiry.store'), $html);
        $this->assertStringContainsString('Семейная фотосессия', $html);
    }

    private function render(string $view, array $data): string
    {
        $this->withViewErrors([]);

        return view($view, $data)->render();
    }
}
