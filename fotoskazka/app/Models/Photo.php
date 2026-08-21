<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['album_id', 'media_id', 'caption', 'sort_order'];

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
