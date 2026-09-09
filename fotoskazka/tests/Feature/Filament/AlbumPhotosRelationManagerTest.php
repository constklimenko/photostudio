<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Filament\Resources\Albums\RelationManagers\PhotosRelationManager;
use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AlbumPhotosRelationManagerTest extends TestCase
{
    use AdminTestCase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_rotate_action_is_available_for_album_photo(): void
    {
        [$album, $photo] = $this->createAlbumWithPhoto();

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditAlbum::class,
        ])->assertTableActionExists('rotate');
    }

    public function test_rotate_action_rotates_album_photo(): void
    {
        [$album, $photo, $media] = $this->createAlbumWithPhoto();

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditAlbum::class,
        ])->callTableAction('rotate', $photo, data: [
            'degrees' => '90',
        ]);

        $media->refresh();

        $this->assertSame(400, $media->width);
        $this->assertSame(600, $media->height);
    }

    public function test_edit_action_updates_media_title_photo_caption_and_order(): void
    {
        [$album, $photo, $media] = $this->createAlbumWithPhoto();

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditAlbum::class,
        ])->callTableAction('edit', $photo, data: [
            'media' => ['title' => 'Новое название'],
            'caption' => 'Новая подпись',
            'sort_order' => '7',
        ]);

        $photo->refresh();
        $media->refresh();

        $this->assertSame('Новое название', $media->title);
        $this->assertSame('Новая подпись', $photo->caption);
        $this->assertSame(7, $photo->sort_order);
    }

    public function test_edit_action_keeps_photo_when_title_not_changed(): void
    {
        [$album, $photo, $media] = $this->createAlbumWithPhoto();

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditAlbum::class,
        ])->callTableAction('edit', $photo, data: [
            'media' => ['title' => $media->title],
            'caption' => 'Подпись изменена',
            'sort_order' => '3',
        ]);

        $photo->refresh();

        $this->assertSame('Подпись изменена', $photo->caption);
        $this->assertSame(3, $photo->sort_order);
    }

    public function test_add_from_album_action_is_available(): void
    {
        $album = Album::factory()->create();

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditAlbum::class,
        ])->assertTableActionExists('addFromAlbum');
    }

    public function test_add_from_album_creates_photos_from_source_album(): void
    {
        $target = Album::factory()->create();
        $source = Album::factory()->create();

        $sourcePhotos = collect();

        foreach (['one.jpg', 'two.jpg'] as $i => $file) {
            UploadedFile::fake()->image($file, 600, 400)->storeAs('images', $file, 'public');

            $media = Media::query()->create([
                'file_path' => 'images/'.$file,
                'disk' => 'public',
                'collection' => 'gallery',
            ]);

            $media->refresh();

            $sourcePhotos->push(Photo::query()->create([
                'album_id' => $source->id,
                'media_id' => $media->id,
                'sort_order' => $i + 1,
            ]));
        }

        $target->photos()->create([
            'media_id' => $sourcePhotos[0]->media_id,
            'caption' => 'Уже существует',
            'sort_order' => 99,
        ]);

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $target,
            'pageClass' => EditAlbum::class,
        ])->callTableAction('addFromAlbum', data: [
            'source_album_id' => $source->id,
            'media_ids' => [$sourcePhotos[1]->media_id],
        ]);

        $target->refresh();

        $this->assertSame(2, $target->photos()->count());

        $this->assertContains($sourcePhotos[1]->media_id, $target->photos()->pluck('media_id'));

        $second = $target->photos()->where('media_id', $sourcePhotos[1]->media_id)->first();

        $this->assertSame(100, $second->sort_order);
    }

    public function test_add_from_album_action_with_empty_media_selection_does_not_add_photos(): void
    {
        $target = Album::factory()->create();
        $source = Album::factory()->create();

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $target,
            'pageClass' => EditAlbum::class,
        ])->callTableAction('addFromAlbum', data: [
            'source_album_id' => $source->id,
            'media_ids' => [],
        ]);

        $this->assertSame(0, $target->photos()->count());
    }

    public function test_add_from_album_action_skips_media_already_in_target_album(): void
    {
        $target = Album::factory()->create();
        $source = Album::factory()->create();

        UploadedFile::fake()->image('photo.jpg', 600, 400)->storeAs('images', 'photo.jpg', 'public');

        $media = Media::query()->create([
            'file_path' => 'images/photo.jpg',
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        $media->refresh();
        $mediaId = $media->id;

        Photo::query()->create([
            'album_id' => $source->id,
            'media_id' => $mediaId,
            'sort_order' => 1,
        ]);

        Photo::query()->create([
            'album_id' => $target->id,
            'media_id' => $mediaId,
            'sort_order' => 1,
        ]);

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $target,
            'pageClass' => EditAlbum::class,
        ])->callTableAction('addFromAlbum', data: [
            'source_album_id' => $source->id,
            'media_ids' => [$mediaId],
        ]);

        $this->assertSame(1, $target->photos()->count());
    }

    /**
     * @return array{0: Album, 1: Photo, 2: Media}
     */
    protected function createAlbumWithPhoto(): array
    {
        $album = Album::factory()->create();

        UploadedFile::fake()->image('photo.jpg', 600, 400)->storeAs('images', 'photo.jpg', 'public');

        $media = Media::query()->create([
            'file_path' => 'images/photo.jpg',
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        $media->refresh();

        $this->assertNotNull($media->thumbnail_path, 'Test precondition: thumbnail must exist');

        $photo = Photo::query()->create([
            'album_id' => $album->id,
            'media_id' => $media->id,
            'caption' => 'Подпись',
            'sort_order' => 1,
        ]);

        return [$album, $photo, $media];
    }
}
