<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArTarget extends Model
{
    use HasFactory;

    public const TOKEN_LENGTH = 12;

    protected $fillable = [
        'title',
        'project_id',
        'photo_id',
        'disk',
        'marker_path',
        'marker_width',
        'marker_height',
        'tracker',
        'tracker_version',
        'target_path',
        'feature_points',
        'compiled_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'marker_width' => 'integer',
            'marker_height' => 'integer',
            'feature_points' => 'integer',
            'compiled_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ArTarget $target) {
            $target->token ??= static::newToken();
            $target->disk ??= Config::get('filesystems.ar_disk', 'public');
        });

        static::deleting(function (ArTarget $target) {
            $target->contents()->get()->each->delete();
            Storage::disk($target->disk)->delete(array_filter([$target->marker_path, $target->target_path]));
        });
    }

    public static function newToken(): string
    {
        do {
            $token = Str::random(self::TOKEN_LENGTH);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function regenerateToken(): void
    {
        $this->forceFill(['token' => static::newToken()])->save();
    }

    public function markerAspect(): ?float
    {
        if (! $this->marker_width || ! $this->marker_height) {
            return null;
        }

        return $this->marker_height / $this->marker_width;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ArContent::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeContents(): HasMany
    {
        return $this->contents()->where('is_active', true);
    }
}
