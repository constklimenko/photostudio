<?php

namespace Tests\Feature\Models;

use App\Actions\Media\DeleteMedia;
use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Filament\AdminTestCase;
use Tests\TestCase;

class MediaReuseSafetyTest extends TestCase
{
    use AdminTestCase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_deleting_photo_keeps_media_and_files(): void
    {
        $media = $this->createMediaWithFiles();
        $album = Album::factory()->create();
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
        ]);

        $photo->delete();

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk('public')->assertExists($media->file_path);
    }

    public function test_media_can_be_attached_to_second_album_after_photo_deleted(): void
    {
        $media = $this->createMediaWithFiles();

        $first = Album::factory()->create();
        Photo::factory()->create(['album_id' => $first->id, 'media_id' => $media->id]);

        $second = Album::factory()->create();
        Photo::factory()->create(['album_id' => $second->id, 'media_id' => $media->id]);

        Photo::query()->where('album_id', $first->id)->delete();

        $this->assertDatabaseHas('photos', ['album_id' => $second->id, 'media_id' => $media->id]);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_deleting_album_keeps_media(): void
    {
        $media = $this->createMediaWithFiles();
        $album = Album::factory()->create([
            'cover_media_id' => $media->id,
        ]);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);

        Livewire::test(EditAlbum::class, ['record' => $album->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('photos', ['album_id' => $album->id]);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk('public')->assertExists($media->file_path);
    }

    public function test_deleting_media_cascades_photos_and_clears_cover(): void
    {
        $media = $this->createMediaWithFiles();
        $album = Album::factory()->create([
            'cover_media_id' => $media->id,
        ]);
        Photo::factory()->create(['album_id' => $album->id, 'media_id' => $media->id]);

        app(DeleteMedia::class)->execute($media);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDatabaseMissing('photos', ['media_id' => $media->id]);
        $this->assertNull($album->fresh()->cover_media_id);
    }

    protected function createMediaWithFiles(): Media
    {
        UploadedFile::fake()->image('photo.jpg', 600, 400)->storeAs('images', 'reuse-photo.jpg', 'public');

        $media = Media::query()->create([
            'title' => 'Reuse photo',
            'file_path' => 'images/reuse-photo.jpg',
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        $media->refresh();

        return $media;
    }
}
