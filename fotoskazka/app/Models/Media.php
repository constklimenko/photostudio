<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Media extends Model
{
    protected $fillable = [
        'title', 'alt_text', 'disk', 'file_path', 'thumbnail_path',
        'mime_type', 'width', 'height', 'file_size', 'collection',
    ];

    public function mediaables(): MorphToMany
    {
        return $this->morphToMany(Mediaable::class, 'mediaable');
    }
}
