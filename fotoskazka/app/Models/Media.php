<?php

namespace App\Models;

use App\Observers\MediaObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'alt_text', 'disk', 'file_path', 'thumbnail_path',
        'mime_type', 'width', 'height', 'file_size', 'collection',
    ];

    public function getUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk($this->disk ?? 'public')->url($this->file_path);
    }

    public function getThumbnailUrl(): ?string
    {
        if (! $this->thumbnail_path) {
            return $this->getUrl();
        }

        return Storage::disk('thumbnails')->url($this->thumbnail_path);
    }

    protected static function booted(): void
    {
        static::observe(MediaObserver::class);
    }
}
