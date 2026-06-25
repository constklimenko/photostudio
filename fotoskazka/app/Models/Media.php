<?php

namespace App\Models;

use App\Observers\MediaObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'alt_text', 'disk', 'file_path', 'thumbnail_path',
        'mime_type', 'width', 'height', 'file_size', 'collection',
    ];

    protected static function booted(): void
    {
        static::observe(MediaObserver::class);
    }
}
