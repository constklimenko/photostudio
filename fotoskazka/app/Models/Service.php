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
        'short_description', 'description', 'examples_title', 'price_from', 'price_note',
        'is_published', 'sort_order', 'seo_title', 'seo_description',
        'show_album_photos', 'featured_album_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_album_photos' => 'boolean',
            'price_from' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function featuredAlbum(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'featured_album_id');
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

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class)
            ->orderBy('videos.sort_order');
    }

    /**
     * Иерархический slug-путь услуги для URL каталога,
     * включающий путь категории: "категория/подкатегория/услуга".
     * Для услуги без категории — просто её slug.
     */
    public function catalogPath(): string
    {
        if (! $this->category) {
            return $this->slug;
        }

        return $this->category->catalogPath().'/'.$this->slug;
    }
}
