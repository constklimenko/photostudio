<?php

namespace Tests\Feature\Components;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContactSettingsRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::forget('site_settings');

        parent::tearDown();
    }

    public function test_header_footer_and_mobile_bar_render_configured_phone(): void
    {
        $phone = '+7 (999) 123-45-67';

        SiteSetting::set('contacts.phone', $phone);
        SiteSetting::set('contacts.email', 'hello@example.com');

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('tel:+79991234567', false);
        $response->assertSee($phone, false);
        $response->assertSee('mailto:hello@example.com', false);
        $response->assertSee('Позвонить', false);
        $response->assertSee('Оставить заявку', false);

        preg_match('/<nav class="hidden md:flex.*?<\/nav>/s', $response->getContent(), $desktopNav);

        $this->assertSame(1, substr_count($desktopNav[0] ?? '', 'href="tel:'));
    }

    public function test_mobile_call_bar_is_hidden_for_incomplete_phone_number(): void
    {
        config(['contacts.phone' => '+7 (___) ___-__-__']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Позвонить', false);
        $response->assertDontSee('tel:+7', false);
    }
}
