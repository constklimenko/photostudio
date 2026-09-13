<?php

namespace Tests\Feature\Models;

use App\Models\Album;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_attached_to_album(): void
    {
        $album = Album::factory()->create(['type' => 'client']);
        $user = User::factory()->create();

        $album->users()->attach($user->id);

        $this->assertDatabaseHas('album_user', [
            'album_id' => $album->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_album_can_have_users(): void
    {
        $album = Album::factory()->create(['type' => 'client']);
        $user = User::factory()->create();

        $album->users()->attach($user->id);

        $this->assertCount(1, $album->users);
        $this->assertTrue($album->users->contains($user));
    }

    public function test_user_can_have_albums(): void
    {
        $user = User::factory()->create();
        $albums = Album::factory()->count(2)->create(['type' => 'client']);

        $user->albums()->attach($albums->pluck('id'));

        $this->assertCount(2, $user->albums);
        $this->assertTrue($user->albums->contains($albums->get(0)));
        $this->assertTrue($user->albums->contains($albums->get(1)));
    }

    public function test_deleting_album_removes_pivot(): void
    {
        $album = Album::factory()->create(['type' => 'client']);
        $user = User::factory()->create();

        $album->users()->attach($user->id);

        $this->assertDatabaseHas('album_user', [
            'album_id' => $album->id,
            'user_id' => $user->id,
        ]);

        $album->delete();

        $this->assertDatabaseMissing('album_user', [
            'album_id' => $album->id,
        ]);
    }

    public function test_deleting_user_removes_pivot(): void
    {
        $album = Album::factory()->create(['type' => 'client']);
        $user = User::factory()->create();

        $album->users()->attach($user->id);

        $this->assertDatabaseHas('album_user', [
            'album_id' => $album->id,
            'user_id' => $user->id,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('album_user', [
            'user_id' => $user->id,
        ]);
    }

    public function test_one_user_can_be_attached_to_multiple_albums(): void
    {
        $user = User::factory()->create();
        $albums = Album::factory()->count(2)->create(['type' => 'client']);

        $albums->each(fn (Album $album) => $album->users()->attach($user->id));

        $this->assertCount(2, $user->albums);
        $this->assertTrue($user->albums->contains($albums->get(0)));
        $this->assertTrue($user->albums->contains($albums->get(1)));
    }

    public function test_one_album_can_have_multiple_users(): void
    {
        $album = Album::factory()->create(['type' => 'client']);
        $users = User::factory()->count(2)->create();

        $album->users()->attach($users->pluck('id'));

        $this->assertCount(2, $album->users);
        $this->assertTrue($album->users->contains($users->get(0)));
        $this->assertTrue($album->users->contains($users->get(1)));
    }
}
