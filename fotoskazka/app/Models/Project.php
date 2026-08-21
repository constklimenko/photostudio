<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'manager_id', 'title', 'slug', 'type',
        'description', 'shooting_date', 'status',
        'contact_name', 'contact_phone', 'contact_email',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function inquiry(): HasOne
    {
        return $this->hasOne(Inquiry::class, 'project_id');
    }
}
