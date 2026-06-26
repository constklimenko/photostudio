<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_system'];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            if ($role->is_system) {
                throw new \LogicException('Системные роли нельзя удалять.');
            }
        });

        static::saving(function (Role $role) {
            if ($role->isDirty('slug') && $role->getOriginal('is_system')) {
                throw new \LogicException('У системных ролей нельзя менять slug.');
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
