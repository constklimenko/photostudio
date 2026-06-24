<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    protected $fillable = [
        'project_id', 'cover_media_id', 'title', 'slug',
        'description', 'is_featured', 'is_published', 'sort_order',
        'seo_title', 'seo_description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
