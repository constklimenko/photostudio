<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    protected $fillable = [
        'cover_media_id', 'title', 'slug', 'excerpt', 'content',
        'seo_title', 'seo_description', 'is_published', 'sort_order',
    ];

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }
}
