<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FaqItem extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'answer', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'faq_item_service');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_faq_item')
            ->where('categories.type', 'service');
    }
}
