<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\ServiceItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceItemResourceTest extends TestCase
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
        $response = $this->get('/admin/service-items');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/service-items/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $item = ServiceItem::factory()->create();

        $response = $this->get("/admin/service-items/{$item->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_service_item(): void
    {
        $item = ServiceItem::factory()->create([
            'label' => 'Retouching',
        ]);

        $this->assertDatabaseHas('service_items', [
            'label' => 'Retouching',
        ]);
    }

    public function test_can_update_service_item(): void
    {
        $item = ServiceItem::factory()->create(['label' => 'Old Label']);

        $item->update(['label' => 'Updated Label']);

        $this->assertDatabaseHas('service_items', [
            'id' => $item->id,
            'label' => 'Updated Label',
        ]);
    }

    public function test_can_delete_service_item(): void
    {
        $item = ServiceItem::factory()->create();

        $item->delete();

        $this->assertDatabaseMissing('service_items', ['id' => $item->id]);
    }

    public function test_service_item_table_displays_data(): void
    {
        $item = ServiceItem::factory()->create(['label' => 'Test Item']);

        $response = $this->get('/admin/service-items');

        $response->assertSuccessful();
        $this->assertDatabaseHas('service_items', ['label' => 'Test Item']);
    }
}
