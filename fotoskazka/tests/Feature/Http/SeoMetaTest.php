<?php

namespace Tests\Feature\Http;

use App\Models\Page;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_has_canonical_without_query_string(): void
    {
        $this->get('/services?utm_source=test')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/services').'">', false)
            ->assertSee('<meta property="og:url" content="'.url('/services').'">', false);
    }

    public function test_home_hero_title_is_h1(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['<h1', 'ФОТОСКАЗКА УФА', '</h1>'], false);
    }

    public function test_long_multiline_meta_description_is_squished_and_limited(): void
    {
        Page::factory()->create([
            'slug' => 'home',
            'seo_description' => "Фотограф в Уфе\n\n<p>Фотосказка — профессиональная фотосъёмка в Уфе.</p>\n\n".str_repeat('Длинный текст «описания». ', 20),
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)">/u', $html, $matches);

        $this->assertNotEmpty($matches);
        $description = html_entity_decode($matches[1], ENT_QUOTES);

        $this->assertStringStartsWith('Фотограф в Уфе Фотосказка — профессиональная фотосъёмка в Уфе.', $description);
        $this->assertStringNotContainsString("\n", $description);
        $this->assertStringNotContainsString('<p>', $description);
        $this->assertLessThanOrEqual(161, mb_strlen($description));
        $this->assertStringEndsWith('…', $description);
    }

    public function test_production_urls_use_app_url_instead_of_request_host(): void
    {
        config(['app.url' => 'https://fotoskazkaufa.ru']);
        $this->app->detectEnvironment(fn () => 'production');
        (new AppServiceProvider($this->app))->boot();

        $this->get('http://83.166.245.105/services')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://fotoskazkaufa.ru/services">', false);

        $this->get('http://83.166.245.105/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://fotoskazkaufa.ru/sitemap.xml');
    }
}
