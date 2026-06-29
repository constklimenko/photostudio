<?php

namespace App\Models;

use Database\Factories\ServiceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceItem extends Model
{
    /** @use HasFactory<ServiceItemFactory> */
    use HasFactory;

    protected $fillable = [
        'service_id', 'label', 'is_included', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
