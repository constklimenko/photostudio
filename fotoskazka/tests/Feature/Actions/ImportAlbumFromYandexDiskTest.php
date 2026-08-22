<?php

namespace Tests\Feature\Actions;

use App\Actions\Album\ImportAlbumFromYandexDisk;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ImportAlbumFromYandexDiskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.yandex_disk.token' => 'test-token']);
        Storage::fake('yandex_disk');
        Storage::fake('thumbnails');
    }

    public function test_imports_album_from_folder(): void
    {
        $disk = Storage::disk('yandex_disk');
        $disk->put('shoot/img10.jpg', $this->makeJpegBytes());
        $disk->put('shoot/img2.jpg', $this->makeJpegBytes());
        $disk->put('shoot/notes.txt', 'not an image');

        $album = app(ImportAlbumFromYandexDisk::class)->execute([
            'title' => 'Выпускной 11А',
            'folder' => 'shoot',
            'type' => 'portfolio',
            'use_first_as_cover' => true,
        ]);

        $album->refresh();

        $this->assertSame('Выпускной 11А', $album->title);
        $this->assertSame('portfolio', $album->type);
        $this->assertStringStartsWith(str('Выпускной 11А')->slug().'-', $album->slug);

        $this->assertCount(2, $album->photos);

        $firstPhoto = $album->photos()->orderBy('sort_order')->first();
        /** @var Media $firstMedia */
        $firstMedia = $firstPhoto->media;

        $this->assertSame('yandex_disk', $firstMedia->disk);
        $this->assertSame('shoot/img2.jpg', $firstMedia->file_path);
        $this->assertEquals($firstMedia->id, $album->cover_media_id);

        $this->assertSame('image/jpeg', $firstMedia->mime_type);
        $this->assertGreaterThan(0, $firstMedia->width);
        $this->assertGreaterThan(0, $firstMedia->height);
        $this->assertNotNull($firstMedia->file_size);

        $this->assertNotNull($firstMedia->thumbnail_path);
        Storage::disk('thumbnails')->assertExists($firstMedia->thumbnail_path);
        $this->assertStringEndsWith('.webp', $firstMedia->thumbnail_path);
    }

    public function test_throws_for_missing_folder(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('не найдена');

        app(ImportAlbumFromYandexDisk::class)->execute([
            'title' => 'Тест',
            'folder' => 'missing-folder',
        ]);
    }

    public function test_throws_when_folder_has_no_images(): void
    {
        Storage::disk('yandex_disk')->put('docs/readme.txt', 'text');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('нет изображений');

        app(ImportAlbumFromYandexDisk::class)->execute([
            'title' => 'Тест',
            'folder' => 'docs',
        ]);
    }

    public function test_respects_max_files_limit(): void
    {
        Config::set('filesystems.yandex_import.max_files', 1);

        $disk = Storage::disk('yandex_disk');
        $disk->put('shoot/a.jpg', $this->makeJpegBytes());
        $disk->put('shoot/b.jpg', $this->makeJpegBytes());

        $album = app(ImportAlbumFromYandexDisk::class)->execute([
            'title' => 'Лимит',
            'folder' => 'shoot',
        ]);

        $this->assertCount(1, $album->photos);
        $this->assertSame(1, $album->imported_files_count);
        $this->assertSame(1, $album->skipped_files_count);
    }

    public function test_yandex_media_url_uses_proxy_route(): void
    {
        Route::getRoutes()->getByName('media.original');

        $disk = Storage::disk('yandex_disk');
        $disk->put('shoot/one.jpg', $this->makeJpegBytes());

        $album = app(ImportAlbumFromYandexDisk::class)->execute([
            'title' => 'Прокси',
            'folder' => 'shoot',
        ]);

        $photo = $album->photos()->orderBy('sort_order')->first();

        $expected = route('media.original', ['media' => $photo->media_id]);

        $this->assertSame($expected, $photo->media->getUrl());
        $this->assertStringStartsWith('/media/', parse_url($expected, PHP_URL_PATH));
    }

    protected function makeJpegBytes(int $width = 100, int $height = 80): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 100, 50));

        ob_start();
        imagejpeg($image, null, 85);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
