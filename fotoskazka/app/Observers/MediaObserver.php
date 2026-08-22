<?php

namespace App\Observers;

use App\Models\Media;
use App\Services\MediaProcessor;

class MediaObserver
{
    public function __construct(protected MediaProcessor $processor) {}

    public function creating(Media $media): void
    {
        $media->disk = $media->disk ?? (string) config('filesystems.default_media_disk', 'public');
    }

    public function created(Media $media): void
    {
        $this->processor->process($media);
    }
}
