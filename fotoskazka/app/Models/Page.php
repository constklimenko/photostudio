<?php

namespace App\Models;

use App\Observers\PageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(PageObserver::class)]
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'cover_media_id', 'title', 'subtitle', 'slug', 'excerpt', 'content',
        'home_title', 'home_subtitle', 'show_on_home', 'home_sort_order', 'menu_title',
        'ar_teaser_enabled', 'ar_teaser_title', 'ar_teaser_subtitle', 'ar_teaser_accent', 'ar_teaser_footer', 'ar_teaser_media_id',
        'seo_title', 'seo_description', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'show_on_home' => 'boolean',
            'ar_teaser_enabled' => 'boolean',
        ];
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function arTeaserMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'ar_teaser_media_id');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'page_album');
    }
}
