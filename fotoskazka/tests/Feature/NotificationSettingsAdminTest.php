<?php

namespace Tests\Feature;

use App\Models\NotificationSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($this->admin);
    }

    public function test_list_page_can_be_rendered(): void
    {
        $response = $this->get('/admin/notification-settings');

        $response->assertStatus(200);
    }

    public function test_edit_page_can_be_rendered(): void
    {
        NotificationSetting::create([
            'title' => 'Основные настройки',
            'email_recipients' => ['admin@example.com'],
        ]);

        $response = $this->get('/admin/notification-settings/1/edit');

        $response->assertStatus(200);
    }
}
