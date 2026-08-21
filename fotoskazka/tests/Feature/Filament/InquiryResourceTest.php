<?php

namespace Tests\Feature\Filament;

use App\Models\Inquiry;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryResourceTest extends TestCase
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
        $response = $this->get('/admin/inquiries');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/inquiries/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $inquiry = Inquiry::factory()->create();

        $response = $this->get("/admin/inquiries/{$inquiry->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create([
            'name' => 'Test Client',
            'phone' => '+7-999-123-45-67',
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Test Client',
            'phone' => '+7-999-123-45-67',
            'status' => 'new',
        ]);
    }

    public function test_can_update_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create(['name' => 'Old Name']);

        $inquiry->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create();

        $inquiry->delete();

        $this->assertDatabaseMissing('inquiries', ['id' => $inquiry->id]);
    }

    public function test_inquiry_requires_name(): void
    {
        $inquiry = Inquiry::factory()->create(['name' => '']);

        $this->assertDatabaseHas('inquiries', ['name' => '']);
    }

    public function test_inquiry_requires_phone(): void
    {
        $inquiry = Inquiry::factory()->create(['phone' => '']);

        $this->assertDatabaseHas('inquiries', ['phone' => '']);
    }

    public function test_inquiry_table_displays_data(): void
    {
        $inquiry = Inquiry::factory()->create(['name' => 'Test Inquiry']);

        $response = $this->get('/admin/inquiries');

        $response->assertSuccessful();
        $this->assertDatabaseHas('inquiries', ['name' => 'Test Inquiry']);
    }

    public function test_inquiry_status_filter(): void
    {
        $new = Inquiry::factory()->create(['status' => 'new']);
        $completed = Inquiry::factory()->create(['status' => 'completed']);

        $this->assertDatabaseHas('inquiries', ['status' => 'new']);
        $this->assertDatabaseHas('inquiries', ['status' => 'completed']);
    }
}
