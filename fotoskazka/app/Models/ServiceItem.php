<?php

namespace App\Models;

use Database\Factories\ServiceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceItem extends Model
{
    /** @use HasFactory<ServiceItemFactory> */
    use HasFactory;

    protected $fillable = [
        'label', 'subtitle', 'is_included', 'sort_order', 'icon_id',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
        ];
    }

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Icon::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('is_included', 'sort_order');
    }
}
