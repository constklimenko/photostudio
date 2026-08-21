<?php

namespace Tests\Unit\Models;

use App\Models\Album;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Media;
use App\Models\Page;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Project;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_belongs_to_many_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->roles->contains($role));
    }

    public function test_user_has_many_projects_as_client(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['client_id' => $user->id]);

        $this->assertTrue($user->projects->contains($project));
    }

    public function test_user_has_many_inquiries(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->inquiries->contains($inquiry));
    }

    public function test_role_belongs_to_many_users(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $role->users()->attach($user);

        $this->assertTrue($role->users->contains($user));
    }

    public function test_category_has_many_services(): void
    {
        $category = Category::factory()->create(['type' => 'service']);
        $service = Service::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->services->contains($service));
    }

    public function test_category_has_many_posts(): void
    {
        $category = Category::factory()->create(['type' => 'post']);
        $post = Post::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->posts->contains($post));
    }

    public function test_service_belongs_to_category(): void
    {
        $category = Category::factory()->create(['type' => 'service']);
        $service = Service::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $service->category);
        $this->assertEquals($category->id, $service->category->id);
    }

    public function test_service_has_many_inquiries(): void
    {
        $service = Service::factory()->create();
        $inquiry = Inquiry::factory()->create(['service_id' => $service->id]);

        $this->assertTrue($service->inquiries->contains($inquiry));
    }

    public function test_project_belongs_to_client(): void
    {
        $client = User::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(User::class, $project->client);
        $this->assertEquals($client->id, $project->client->id);
    }

    public function test_project_has_many_albums(): void
    {
        $project = Project::factory()->create();
        $album = Album::factory()->create(['project_id' => $project->id]);

        $this->assertTrue($project->albums->contains($album));
    }

    public function test_album_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $album = Album::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $album->project);
    }

    public function test_album_belongs_to_project_nullable(): void
    {
        $album = Album::factory()->create(['project_id' => null]);

        $this->assertNull($album->project);
    }

    public function test_album_has_many_photos(): void
    {
        $album = Album::factory()->create();
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $this->assertTrue($album->photos->contains($photo));
    }

    public function test_photo_belongs_to_album(): void
    {
        $album = Album::factory()->create();
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $this->assertInstanceOf(Album::class, $photo->album);
    }

    public function test_photo_belongs_to_media(): void
    {
        $media = Media::factory()->create();
        $photo = Photo::factory()->create(['media_id' => $media->id]);

        $this->assertInstanceOf(Media::class, $photo->media);
    }

    public function test_post_belongs_to_category(): void
    {
        $category = Category::factory()->create(['type' => 'post']);
        $post = Post::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $post->category);
    }

    public function test_page_belongs_to_cover(): void
    {
        $media = Media::factory()->create();
        $page = Page::factory()->create(['cover_media_id' => $media->id]);

        $this->assertInstanceOf(Media::class, $page->cover);
    }

    public function test_testimonial_belongs_to_photo(): void
    {
        $media = Media::factory()->create();
        $testimonial = Testimonial::factory()->create(['media_id' => $media->id]);

        $this->assertInstanceOf(Media::class, $testimonial->photo);
    }

    public function test_inquiry_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $inquiry->user);
    }

    public function test_inquiry_belongs_to_service(): void
    {
        $service = Service::factory()->create();
        $inquiry = Inquiry::factory()->create(['service_id' => $service->id]);

        $this->assertInstanceOf(Service::class, $inquiry->service);
    }

    public function test_album_type_default(): void
    {
        $album = Album::factory()->create(['type' => 'portfolio']);

        $this->assertEquals('portfolio', $album->type);
    }

    public function test_album_has_type_field(): void
    {
        $album = Album::factory()->create(['type' => 'client']);

        $this->assertEquals('client', $album->type);
    }

    public function test_service_belongs_to_many_items(): void
    {
        $service = Service::factory()->create();
        $items = ServiceItem::factory()->count(3)->create();
        $service->items()->attach($items->pluck('id'));

        $this->assertCount(3, $service->items);
        $this->assertInstanceOf(ServiceItem::class, $service->items->first());
    }

    public function test_service_item_belongs_to_many_services(): void
    {
        $service = Service::factory()->create();
        $item = ServiceItem::factory()->create();
        $service->items()->attach($item);

        $this->assertTrue($item->services->contains($service));
    }

    public function test_service_item_casts_is_included_to_boolean(): void
    {
        $item = ServiceItem::factory()->create(['is_included' => true]);

        $this->assertTrue($item->is_included);
    }

    public function test_service_belongs_to_many_albums(): void
    {
        $service = Service::factory()->create();
        $albums = Album::factory()->count(2)->create();
        $service->albums()->attach($albums->pluck('id'));

        $this->assertCount(2, $service->albums);
        $this->assertInstanceOf(Album::class, $service->albums->first());
    }

    public function test_inquiry_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $inquiry = Inquiry::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $inquiry->project);
        $this->assertEquals($project->id, $inquiry->project->id);
    }

    public function test_project_has_one_inquiry(): void
    {
        $project = Project::factory()->create();
        $inquiry = Inquiry::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Inquiry::class, $project->inquiry);
        $this->assertEquals($inquiry->id, $project->inquiry->id);
    }

    public function test_album_belongs_to_many_videos(): void
    {
        $album = Album::factory()->create();
        $videos = Video::factory()->count(2)->create();
        $album->videos()->attach($videos->pluck('id'));

        $this->assertCount(2, $album->videos);
        $this->assertInstanceOf(Video::class, $album->videos->first());
    }

    public function test_video_belongs_to_many_albums(): void
    {
        $album = Album::factory()->create();
        $video = Video::factory()->create();
        $album->videos()->attach($video);

        $this->assertTrue($video->albums->contains($album));
    }

    public function test_album_videos_ordered_by_pivot_sort_order(): void
    {
        $album = Album::factory()->create();
        $first = Video::factory()->create(['title' => 'Первый ролик']);
        $second = Video::factory()->create(['title' => 'Второй ролик']);

        $album->videos()->attach($second, ['sort_order' => 2]);
        $album->videos()->attach($first, ['sort_order' => 1]);

        $this->assertEquals(['Первый ролик', 'Второй ролик'], $album->videos->pluck('title')->all());
        $this->assertEquals(1, $album->videos->first()->pivot->sort_order);
    }

    public function test_service_belongs_to_many_videos(): void
    {
        $service = Service::factory()->create();
        $videos = Video::factory()->count(2)->create();
        $service->videos()->attach($videos->pluck('id'));

        $this->assertCount(2, $service->videos);
        $this->assertInstanceOf(Video::class, $service->videos->first());
    }

    public function test_post_belongs_to_many_videos(): void
    {
        $post = Post::factory()->create();
        $videos = Video::factory()->count(2)->create();
        $post->videos()->attach($videos->pluck('id'));

        $this->assertCount(2, $post->videos);
        $this->assertInstanceOf(Video::class, $post->videos->first());
    }

    public function test_video_belongs_to_many_services(): void
    {
        $service = Service::factory()->create();
        $video = Video::factory()->create();
        $service->videos()->attach($video);

        $this->assertTrue($video->services->contains($service));
    }

    public function test_video_belongs_to_many_posts(): void
    {
        $post = Post::factory()->create();
        $video = Video::factory()->create();
        $post->videos()->attach($video);

        $this->assertTrue($video->posts->contains($post));
    }

    public function test_service_videos_ordered_by_video_sort_order(): void
    {
        $service = Service::factory()->create();
        $first = Video::factory()->create(['title' => 'Первый ролик', 'sort_order' => 1]);
        $second = Video::factory()->create(['title' => 'Второй ролик', 'sort_order' => 5]);

        $service->videos()->attach($second);
        $service->videos()->attach($first);

        $this->assertEquals(['Первый ролик', 'Второй ролик'], $service->videos->pluck('title')->all());
    }
}
