<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class ArContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'ar_target_id',
        'type',
        'disk',
        'file_path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'duration_ms',
        'has_sound',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_ms' => 'integer',
            'has_sound' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ArContent $content) {
            $content->disk ??= Config::get('filesystems.ar_disk', 'public');
        });

        static::deleted(function (ArContent $content) {
            Storage::disk($content->disk)->delete($content->file_path);
        });
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(ArTarget::class, 'ar_target_id');
    }
}
