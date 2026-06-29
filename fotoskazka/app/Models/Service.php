<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'cover_media_id', 'title', 'slug',
        'short_description', 'description', 'price_from', 'price_note',
        'is_published', 'sort_order', 'seo_title', 'seo_description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ServiceItem::class)
            ->withPivot('is_included', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class);
    }
}
