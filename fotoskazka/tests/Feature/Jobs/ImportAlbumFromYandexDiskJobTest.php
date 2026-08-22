<?php

namespace Tests\Feature\Jobs;

use App\Actions\Album\ImportAlbumFromYandexDisk as ImportAction;
use App\Jobs\ImportAlbumFromYandexDisk as ImportJob;
use App\Jobs\ProcessMedia;
use App\Models\Album;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ImportAlbumFromYandexDiskJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('yandex_disk');
    }

    public function test_imports_album_from_folder(): void
    {
        foreach ([1, 2, 3] as $i) {
            UploadedFile::fake()->image("photo{$i}.jpg")->storeAs('japanki', "photo{$i}.jpg", 'yandex_disk');
        }

        Queue::fake();

        (new ImportJob([
            'title' => 'Японки',
            'type' => 'portfolio',
            'description' => null,
            'folder' => 'japanki',
            'use_first_as_cover' => true,
        ]))->handle(app(ImportAction::class));

        $album = Album::query()->where('title', 'Японки')->first();

        $this->assertNotNull($album);
        $this->assertSame(3, $album->photos()->count());
        $this->assertSame('japanki/photo1.jpg', $album->cover?->file_path);
        $this->assertSame(3, Media::query()->where('disk', 'yandex_disk')->where('file_path', 'like', 'japanki/%')->count());

        Queue::assertPushed(ProcessMedia::class, 3);
    }

    public function test_missing_folder_fails_job_with_exception(): void
    {
        $this->expectException(RuntimeException::class);

        (new ImportJob([
            'title' => 'Нет папки',
            'type' => 'portfolio',
            'folder' => 'nonexistent',
        ]))->handle(app(ImportAction::class));

        $this->assertDatabaseMissing('albums', ['title' => 'Нет папки']);
    }

    public function test_retry_configuration_is_sane_for_listing_and_inserts(): void
    {
        $job = new ImportJob(['type' => 'portfolio', 'folder' => 'x']);

        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 120], $job->backoff());
        $this->assertSame(300, $job->timeout);
    }

    public function test_unique_id_depends_on_disk_type_and_folder(): void
    {
        $job = new ImportJob(['type' => 'portfolio', 'folder' => 'японки']);
        $same = new ImportJob(['type' => 'portfolio', 'folder' => 'японки']);
        $otherFolder = new ImportJob(['type' => 'portfolio', 'folder' => 'другая']);
        $otherType = new ImportJob(['type' => 'client', 'folder' => 'японки']);
        $otherDisk = new ImportJob(['type' => 'portfolio', 'folder' => 'японки'], 'other_disk');

        $this->assertSame($job->uniqueId(), $same->uniqueId());
        $this->assertNotSame($job->uniqueId(), $otherFolder->uniqueId());
        $this->assertNotSame($job->uniqueId(), $otherType->uniqueId());
        $this->assertNotSame($job->uniqueId(), $otherDisk->uniqueId());
    }

    public function test_duplicate_dispatch_for_same_folder_is_dropped(): void
    {
        config(['queue.default' => 'database']);

        $data = ['type' => 'portfolio', 'title' => 'Японки', 'folder' => 'японки'];

        ImportJob::dispatch($data);
        ImportJob::dispatch($data);

        $this->assertDatabaseCount('jobs', 1);

        ImportJob::dispatch(['type' => 'portfolio', 'title' => 'Другая', 'folder' => 'другая']);

        $this->assertDatabaseCount('jobs', 2);
    }
}
