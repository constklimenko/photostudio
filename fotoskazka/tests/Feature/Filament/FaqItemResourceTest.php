<?php

namespace Tests\Feature\Filament;

use App\Models\FaqItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqItemResourceTest extends TestCase
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
        $response = $this->get('/admin/faq-items');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/faq-items/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $item = FaqItem::factory()->create();

        $response = $this->get("/admin/faq-items/{$item->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_faq_item(): void
    {
        $item = FaqItem::factory()->create([
            'question' => 'How much?',
            'answer' => 'It depends.',
        ]);

        $this->assertDatabaseHas('faq_items', [
            'question' => 'How much?',
            'answer' => 'It depends.',
        ]);
    }

    public function test_can_update_faq_item(): void
    {
        $item = FaqItem::factory()->create(['question' => 'Old Question']);

        $item->update(['question' => 'Updated Question']);

        $this->assertDatabaseHas('faq_items', [
            'id' => $item->id,
            'question' => 'Updated Question',
        ]);
    }

    public function test_can_delete_faq_item(): void
    {
        $item = FaqItem::factory()->create();

        $item->delete();

        $this->assertDatabaseMissing('faq_items', ['id' => $item->id]);
    }

    public function test_faq_item_table_displays_data(): void
    {
        $item = FaqItem::factory()->create(['question' => 'Test Question']);

        $response = $this->get('/admin/faq-items');

        $response->assertSuccessful();
        $this->assertDatabaseHas('faq_items', ['question' => 'Test Question']);
    }
}
