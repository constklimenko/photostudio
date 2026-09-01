<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'url', 'file_path', 'type', 'rotate_90', 'sort_order', 'is_active', 'show_on_home'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'rotate_90' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function getDefaultDisk(): string
    {
        return Config::get('filesystems.default_media_disk', 'public');
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return Storage::disk($this->getDefaultDisk())->url($this->file_path);
        }

        if (! $this->url) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $this->url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $this->url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }
        if (preg_match('/rutube\.ru\/video\/([a-zA-Z0-9]+)/', $this->url, $m)) {
            return 'https://rutube.ru/play/embed/'.$m[1];
        }
        if (preg_match('/vkvideo\.ru\/(?:video|clip)(-?\d+)_(\d+)/', $this->url, $m)) {
            return 'https://vk.com/video_ext.php?oid='.$m[1].'&id='.$m[2];
        }
        if (preg_match('/vk\.com\/video(-?\d+)_(\d+)/', $this->url, $m)) {
            return 'https://vk.com/video_ext.php?oid='.$m[1].'&id='.$m[2];
        }
        if (str_contains($this->url, 'vk.com/video_ext.php')) {
            return $this->url;
        }

        return $this->url;
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_video')
            ->withPivot('caption', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_video');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $this->url, $m)) {
            return 'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg';
        }

        return null;
    }

    public function getIsUploadAttribute(): bool
    {
        return ! is_null($this->file_path);
    }

    public function getSourceUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return route('video.stream', $this->id);
        }

        return $this->url;
    }
}
