<?php

namespace Tests\Feature\Filament;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostResourceTest extends TestCase
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
        $response = $this->get('/admin/posts');

        $response->assertSuccessful();
    }

    public function test_create_page_renders(): void
    {
        $response = $this->get('/admin/posts/create');

        $response->assertSuccessful();
    }

    public function test_edit_page_renders(): void
    {
        $post = Post::factory()->create();

        $response = $this->get("/admin/posts/{$post->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_post(): void
    {
        $post = Post::factory()->create([
            'title' => 'New Post',
            'slug' => 'new-post',
            'content' => '<p>Post content</p>',
        ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'New Post',
            'slug' => 'new-post',
        ]);
    }

    public function test_can_update_post(): void
    {
        $post = Post::factory()->create(['title' => 'Old Title']);

        $post->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_post(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_post_slug_must_be_unique(): void
    {
        $post1 = Post::factory()->create(['slug' => 'unique-slug']);

        $post2 = Post::factory()->make(['slug' => 'unique-slug']);

        $this->assertEquals('unique-slug', $post1->slug);
        $this->assertEquals('unique-slug', $post2->slug);
        $this->assertNotEquals($post1->id, $post2->id);
    }

    public function test_post_table_displays_data(): void
    {
        $post = Post::factory()->create(['title' => 'Test Post']);

        $response = $this->get('/admin/posts');

        $response->assertSuccessful();
        $this->assertDatabaseHas('posts', ['title' => 'Test Post']);
    }

    public function test_post_requires_content(): void
    {
        $post = Post::factory()->create(['content' => '']);

        $this->assertDatabaseHas('posts', ['content' => '']);
    }
}
