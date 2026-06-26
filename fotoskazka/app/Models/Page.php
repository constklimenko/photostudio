<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'cover_media_id', 'title', 'slug', 'excerpt', 'content',
        'seo_title', 'seo_description', 'is_published', 'sort_order',
    ];

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'page_album');
    }
}
