<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\SocialLink;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinkResourceTest extends TestCase
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
        $response = $this->get('/admin/social-links');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/social-links/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $link = SocialLink::factory()->create();

        $response = $this->get("/admin/social-links/{$link->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_social_link(): void
    {
        $link = SocialLink::factory()->create([
            'name' => 'Instagram',
            'icon' => 'instagram',
            'url' => 'https://instagram.com/test',
        ]);

        $this->assertDatabaseHas('social_links', [
            'name' => 'Instagram',
            'icon' => 'instagram',
            'url' => 'https://instagram.com/test',
        ]);
    }

    public function test_can_update_social_link(): void
    {
        $link = SocialLink::factory()->create(['name' => 'Old Name']);

        $link->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('social_links', [
            'id' => $link->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_social_link(): void
    {
        $link = SocialLink::factory()->create();

        $link->delete();

        $this->assertDatabaseMissing('social_links', ['id' => $link->id]);
    }

    public function test_social_link_table_displays_data(): void
    {
        $link = SocialLink::factory()->create(['name' => 'Test Link']);

        $response = $this->get('/admin/social-links');

        $response->assertSuccessful();
        $this->assertDatabaseHas('social_links', ['name' => 'Test Link']);
    }
}
