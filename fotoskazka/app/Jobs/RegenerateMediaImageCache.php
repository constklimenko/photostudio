<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RegenerateMediaImageCache implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $mediaId, public bool $force = false)
    {
        $this->afterCommit = true;
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(MediaProcessor $processor): void
    {
        $media = Media::query()->find($this->mediaId);

        if ($media === null) {
            Log::warning('RegenerateMediaImageCache: media record not found.', [
                'media_id' => $this->mediaId,
            ]);

            return;
        }

        $processor->regenerateImageCacheOrFail($media, $this->force);
    }
}
