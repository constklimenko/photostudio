<?php

namespace Tests\Unit\Services;

use App\Models\SiteSetting;
use App\Services\ContactSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContactSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected string $fallbackPhone = '+7 (000) 000-00-00';

    protected string $fallbackEmail = 'fallback@example.org';

    protected string $fallbackAddress = 'ул. Тестовая, 1';

    protected string $fallbackCity = 'Уфа';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'contacts.phone' => $this->fallbackPhone,
            'contacts.email' => $this->fallbackEmail,
            'contacts.address' => $this->fallbackAddress,
            'contacts.city' => $this->fallbackCity,
        ]);
    }

    protected function tearDown(): void
    {
        Cache::forget('site_settings');

        parent::tearDown();
    }

    public function test_falls_back_to_config_when_no_db_value(): void
    {
        $this->assertSame($this->fallbackPhone, ContactSettings::phone());
        $this->assertSame($this->fallbackEmail, ContactSettings::email());
        $this->assertSame($this->fallbackAddress, ContactSettings::address());
        $this->assertSame($this->fallbackCity, ContactSettings::city());
    }

    public function test_db_value_overrides_config(): void
    {
        SiteSetting::set('contacts.phone', '+7 (999) 123-45-67');
        SiteSetting::set('contacts.email', 'hello@example.com');
        SiteSetting::set('contacts.address', 'пр. Октября, 100');
        SiteSetting::set('contacts.city', 'Стерлитамак');

        $this->assertSame('+7 (999) 123-45-67', ContactSettings::phone());
        $this->assertSame('hello@example.com', ContactSettings::email());
        $this->assertSame('пр. Октября, 100', ContactSettings::address());
        $this->assertSame('Стерлитамак', ContactSettings::city());
    }

    public function test_clearing_db_value_returns_to_config(): void
    {
        SiteSetting::set('contacts.phone', '+7 (999) 123-45-67');
        SiteSetting::set('contacts.phone', '');

        $this->assertSame($this->fallbackPhone, ContactSettings::phone());
        $this->assertDatabaseMissing('site_settings', ['key' => 'contacts.phone']);
    }

    public function test_phone_href_strips_formatting(): void
    {
        SiteSetting::set('contacts.phone', '+7 (999) 123-45-67');

        $this->assertSame('tel:+79991234567', ContactSettings::phoneHref());
    }

    public function test_whitespace_value_is_treated_as_empty(): void
    {
        SiteSetting::set('contacts.phone', '   ');

        $this->assertSame($this->fallbackPhone, ContactSettings::phone());
        $this->assertDatabaseMissing('site_settings', ['key' => 'contacts.phone']);
    }

    public function test_null_when_no_value_anywhere(): void
    {
        config(['contacts.phone' => null]);

        $this->assertNull(ContactSettings::phone());
        $this->assertNull(ContactSettings::phoneHref());
    }

    public function test_phone_href_without_plus_prefix_has_no_plus(): void
    {
        SiteSetting::set('contacts.phone', '8 999 123-45-67');

        $this->assertSame('tel:89991234567', ContactSettings::phoneHref());
    }

    public function test_phone_href_is_null_for_incomplete_number(): void
    {
        config(['contacts.phone' => '+7 (___) ___-__-__']);

        $this->assertNull(ContactSettings::phoneHref());
    }

    public function test_email_settings_are_not_affected(): void
    {
        SiteSetting::set('contacts.email', 'hello@example.com');

        $this->assertSame($this->fallbackPhone, ContactSettings::phone());
        $this->assertSame('hello@example.com', ContactSettings::email());
    }
}
