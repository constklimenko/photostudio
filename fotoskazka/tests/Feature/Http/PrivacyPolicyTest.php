<?php

namespace Tests\Feature\Http;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    private function createPrivacyPage(array $attributes = []): Page
    {
        $page = Page::factory()->create(array_merge([
            'slug' => 'privacy',
            'title' => 'Политика конфиденциальности',
            'is_published' => true,
        ], $attributes));

        Cache::flush();

        return $page;
    }

    public function test_privacy_page_renders_published_page(): void
    {
        $this->createPrivacyPage([
            'subtitle' => 'Как мы собираем, используем и защищаем ваши данные',
            'content' => '<h2>1. Общие положения</h2><p>Оператор обрабатывает персональные данные в соответствии с 152-ФЗ.</p>',
        ]);

        $response = $this->get('/privacy');

        $response->assertOk()
            ->assertSee('Политика конфиденциальности')
            ->assertSee('Как мы собираем, используем и защищаем ваши данные')
            ->assertSee('1. Общие положения')
            ->assertSee('152-ФЗ')
            ->assertSee('Редакция от')
            ->assertSee('<h1', false);
    }

    public function test_privacy_page_uses_seo_fields(): void
    {
        $this->createPrivacyPage([
            'seo_title' => 'Политика ПД — Фотосказка',
            'seo_description' => 'Описание политики для поиска',
        ]);

        $response = $this->get('/privacy');

        $response->assertOk()
            ->assertSee('<title>Политика ПД — Фотосказка</title>', false)
            ->assertSee('Описание политики для поиска');
    }

    public function test_privacy_page_shows_contacts_from_settings(): void
    {
        config(['contacts.phone' => '+7 (999) 123-45-67', 'contacts.email' => 'privacy@example.com']);

        $this->createPrivacyPage();

        $response = $this->get('/privacy');

        $response->assertOk()
            ->assertSee('По вопросам обработки персональных данных')
            ->assertSee('mailto:privacy@example.com', false)
            ->assertSee('tel:+79991234567', false);
    }

    public function test_privacy_page_hides_contact_block_without_contacts(): void
    {
        config(['contacts.phone' => null, 'contacts.email' => null]);

        $this->createPrivacyPage();

        $response = $this->get('/privacy');

        $response->assertOk()
            ->assertDontSee('По вопросам обработки персональных данных');
    }

    public function test_privacy_page_returns_404_when_page_missing(): void
    {
        $response = $this->get('/privacy');

        $response->assertNotFound();
    }

    public function test_privacy_page_returns_404_when_unpublished(): void
    {
        $this->createPrivacyPage(['is_published' => false]);

        $response = $this->get('/privacy');

        $response->assertNotFound();
    }

    public function test_footer_contains_privacy_link(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Политика конфиденциальности')
            ->assertSee('href="'.route('privacy').'"', false);
    }

    public function test_inquiry_form_links_to_privacy_policy(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Соглашаюсь с', false)
            ->assertSee('политикой конфиденциальности', false);
    }

    public function test_sitemap_contains_published_privacy_page(): void
    {
        $this->createPrivacyPage();

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertSee(url('/privacy'), false);
    }

    public function test_sitemap_omits_unpublished_privacy_page(): void
    {
        $this->createPrivacyPage(['is_published' => false]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertDontSee(url('/privacy'), false);
    }
}
