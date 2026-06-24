<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = ['media_id', 'client_name', 'content', 'sort_order', 'is_published'];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
