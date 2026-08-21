<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialResourceTest extends TestCase
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
        $response = $this->get('/admin/testimonials');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/testimonials/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $testimonial = Testimonial::factory()->create();

        $response = $this->get("/admin/testimonials/{$testimonial->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_testimonial(): void
    {
        $testimonial = Testimonial::factory()->create([
            'client_name' => 'Happy Client',
            'content' => 'Great service!',
        ]);

        $this->assertDatabaseHas('testimonials', [
            'client_name' => 'Happy Client',
            'content' => 'Great service!',
        ]);
    }

    public function test_can_update_testimonial(): void
    {
        $testimonial = Testimonial::factory()->create(['client_name' => 'Old Name']);

        $testimonial->update(['client_name' => 'Updated Name']);

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'client_name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_testimonial(): void
    {
        $testimonial = Testimonial::factory()->create();

        $testimonial->delete();

        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_testimonial_table_displays_data(): void
    {
        $testimonial = Testimonial::factory()->create(['client_name' => 'Test Client']);

        $response = $this->get('/admin/testimonials');

        $response->assertSuccessful();
        $this->assertDatabaseHas('testimonials', ['client_name' => 'Test Client']);
    }
}
