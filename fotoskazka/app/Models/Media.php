<?php

namespace App\Models;

use App\Observers\MediaObserver;
use App\Services\ImageCacheService;
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

        $disk = $this->disk ?? 'public';

        if ($this->isRemoteDisk($disk)) {
            return route('media.original', ['media' => $this->getKey()]);
        }

        return Storage::disk($disk)->url($this->file_path);
    }

    public function isRemoteDisk(string $disk): bool
    {
        return (bool) config("filesystems.disks.{$disk}.remote", false);
    }

    public function getThumbnailUrl(): ?string
    {
        if (! $this->thumbnail_path) {
            return $this->getUrl();
        }

        return Storage::disk('thumbnails')->url($this->thumbnail_path);
    }

    public function getDisplayUrl(): ?string
    {
        return app(ImageCacheService::class)->url($this, ImageCacheService::TIER_DISPLAY);
    }

    public function getLightboxUrl(): ?string
    {
        return app(ImageCacheService::class)->url($this, ImageCacheService::TIER_LIGHTBOX);
    }

    protected static function booted(): void
    {
        static::observe(MediaObserver::class);
    }
}
