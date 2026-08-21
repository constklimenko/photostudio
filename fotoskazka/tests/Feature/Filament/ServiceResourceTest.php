<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($admin);
    }

    public function test_list_page_renders(): void
    {
        $response = $this->get('/admin/services');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/services/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $service = Service::factory()->create();

        $response = $this->get("/admin/services/{$service->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_service(): void
    {
        $service = Service::factory()->create([
            'title' => 'New Service',
            'slug' => 'new-service',
        ]);

        $this->assertDatabaseHas('services', [
            'title' => 'New Service',
            'slug' => 'new-service',
        ]);
    }

    public function test_can_update_service(): void
    {
        $service = Service::factory()->create(['title' => 'Old Title']);

        $service->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_service(): void
    {
        $service = Service::factory()->create();

        $service->delete();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_service_slug_must_be_unique(): void
    {
        $service1 = Service::factory()->create(['slug' => 'unique-slug']);

        $service2 = Service::factory()->make(['slug' => 'unique-slug']);

        $this->assertEquals('unique-slug', $service1->slug);
        $this->assertEquals('unique-slug', $service2->slug);
        $this->assertNotEquals($service1->id, $service2->id);
    }

    public function test_service_table_displays_data(): void
    {
        $service = Service::factory()->create(['title' => 'Test Service']);

        $response = $this->get('/admin/services');

        $response->assertSuccessful();
        $this->assertDatabaseHas('services', ['title' => 'Test Service']);
    }

    public function test_service_is_published_filter(): void
    {
        $published = Service::factory()->create(['is_published' => true]);
        $draft = Service::factory()->create(['is_published' => false]);

        $this->assertDatabaseHas('services', ['is_published' => true]);
        $this->assertDatabaseHas('services', ['is_published' => false]);
    }
}
