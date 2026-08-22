<?php

namespace App\Observers;

use App\Jobs\ProcessMedia;
use App\Models\Media;

class MediaObserver
{
    public function creating(Media $media): void
    {
        $media->disk = $media->disk ?? (string) config('filesystems.default_media_disk', 'public');
    }

    public function created(Media $media): void
    {
        ProcessMedia::dispatch($media->id);
    }
}
