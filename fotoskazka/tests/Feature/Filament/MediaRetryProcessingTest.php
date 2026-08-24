<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Services\MediaProcessor;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaRetryProcessingTest extends TestCase
{
    use AdminTestCase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('yandex_disk');
        Storage::fake('thumbnails');
        Storage::fake('image_cache');
    }

    public function test_retry_action_is_hidden_for_processed_media_in_list(): void
    {
        $media = $this->createProcessedMedia();

        Livewire::test(ListMedia::class)
            ->assertActionHidden(TestAction::make('retryProcessing')->table($media));
    }

    public function test_retry_action_is_visible_for_pending_media_in_list(): void
    {
        $media = $this->createPendingMedia();

        Livewire::test(ListMedia::class)
            ->assertActionVisible(TestAction::make('retryProcessing')->table($media));
    }

    public function test_retry_action_dispatches_process_media_job_from_list(): void
    {
        Queue::fake();

        $media = $this->createPendingMedia();

        Livewire::test(ListMedia::class)
            ->callAction(TestAction::make('retryProcessing')->table($media));

        Queue::assertPushed(ProcessMedia::class, fn (ProcessMedia $job): bool => $job->mediaId === $media->id);
    }

    public function test_retry_action_on_edit_page_depends_on_processing_state(): void
    {
        Queue::fake();

        $processed = $this->createProcessedMedia();
        $pending = $this->createPendingMedia();

        Livewire::test(EditMedia::class, ['record' => $processed->getKey()])
            ->assertActionHidden('retryProcessing');

        Livewire::test(EditMedia::class, ['record' => $pending->getKey()])
            ->assertActionVisible('retryProcessing')
            ->callAction('retryProcessing');

        Queue::assertPushed(ProcessMedia::class, fn (ProcessMedia $job): bool => $job->mediaId === $pending->id);
    }

    public function test_is_pending_reflects_processing_state(): void
    {
        $processor = app(MediaProcessor::class);

        $pending = $this->createPendingMedia();
        $processed = $this->createProcessedMedia();

        $this->assertTrue($processor->isPending($pending));
        $this->assertFalse($processor->isPending($processed));
    }

    public function test_retried_processing_completes_media(): void
    {
        $media = $this->createPendingMedia();
        $media->refresh();

        $processor = app(MediaProcessor::class);
        $this->assertTrue($processor->processOrFail($media));
        $media->refresh();

        $this->assertFalse($processor->isPending($media));
        $this->assertNotNull($media->thumbnail_path);
    }

    protected function createProcessedMedia(): Media
    {
        $media = $this->createMediaWithFiles('public');

        if ($media->thumbnail_path === null) {
            app(MediaProcessor::class)->processOrFail($media);
            $media->refresh();
        }

        $this->assertNotNull($media->thumbnail_path, 'Test precondition: thumbnail must exist');

        return $media;
    }

    protected function createMediaWithFiles(string $disk, string $filename = 'photo.jpg'): Media
    {
        UploadedFile::fake()->image($filename, 600, 400)->storeAs('images', $filename, $disk);

        $media = Media::query()->create([
            'file_path' => 'images/'.$filename,
            'disk' => $disk,
            'collection' => 'gallery',
        ]);

        return $media->refresh();
    }

    protected function createPendingMedia(): Media
    {
        Queue::fake();

        UploadedFile::fake()->image('pending.jpg', 500, 500)->storeAs('images', 'pending.jpg', 'public');

        $media = Media::query()->create([
            'file_path' => 'images/pending.jpg',
            'disk' => 'public',
            'collection' => 'gallery',
        ]);

        $media->refresh();

        $this->assertNull($media->thumbnail_path, 'Test precondition: media must be unprocessed');

        return $media;
    }
}
