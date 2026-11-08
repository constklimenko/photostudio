<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageContacts;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ManageContactsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        Cache::forget('site_settings');

        parent::tearDown();
    }

    public function test_page_can_be_rendered(): void
    {
        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
    }

    public function test_form_is_filled_from_settings(): void
    {
        SiteSetting::set('contacts.phone', '+7 (999) 123-45-67');
        SiteSetting::set('contacts.email', 'hello@example.com');
        SiteSetting::set('contacts.address', 'ул. 50 лет СССР');
        SiteSetting::set('contacts.city', 'Уфа');

        Livewire::test(ManageContacts::class)
            ->assertSet('data.phone', '+7 (999) 123-45-67')
            ->assertSet('data.email', 'hello@example.com')
            ->assertSet('data.address', 'ул. 50 лет СССР')
            ->assertSet('data.city', 'Уфа');
    }

    public function test_save_persists_settings(): void
    {
        Livewire::test(ManageContacts::class)
            ->set('data.phone', '+7 (999) 555-00-11')
            ->set('data.email', 'admin@example.com')
            ->set('data.address', 'ул. 50 лет СССР')
            ->set('data.city', 'Уфа')
            ->call('save')
            ->assertHasNoActionErrors();

        $this->assertSame('+7 (999) 555-00-11', SiteSetting::get('contacts.phone'));
        $this->assertSame('admin@example.com', SiteSetting::get('contacts.email'));
        $this->assertSame('ул. 50 лет СССР', SiteSetting::get('contacts.address'));
        $this->assertSame('Уфа', SiteSetting::get('contacts.city'));
    }

    public function test_email_validation_rejects_invalid_value(): void
    {
        Livewire::test(ManageContacts::class)
            ->set('data.phone', '')
            ->set('data.email', 'not-an-email')
            ->call('save')
            ->assertHasActionErrors();

        $this->assertDatabaseMissing('site_settings', ['key' => 'contacts.email']);
    }

    public function test_clearing_settings_deletes_rows(): void
    {
        SiteSetting::set('contacts.phone', '+7 (999) 555-00-11');

        Livewire::test(ManageContacts::class)
            ->set('data.phone', '')
            ->set('data.email', '')
            ->call('save')
            ->assertHasNoActionErrors();

        $this->assertDatabaseCount('site_settings', 0);
    }
}
