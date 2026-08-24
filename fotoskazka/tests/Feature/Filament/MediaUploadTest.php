<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use AdminTestCase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_single_upload_creates_media_and_defers_processing(): void
    {
        Queue::fake();

        $path = UploadedFile::fake()->image('photo.jpg', 640, 480)->store('images', 'public');

        Livewire::test(CreateMedia::class)
            ->fillForm([
                'title' => 'Загруженный файл',
                'file_path' => [$path],
                'collection' => 'gallery',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $media = Media::query()->where('title', 'Загруженный файл')->firstOrFail();

        $this->assertSame($path, $media->file_path);
        $this->assertSame('public', $media->disk);

        Queue::assertPushed(ProcessMedia::class, fn (ProcessMedia $job): bool => $job->mediaId === $media->id);
    }
}
