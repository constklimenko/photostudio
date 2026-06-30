<?php

namespace App\Models;

use App\Observers\InquiryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(InquiryObserver::class)]

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'service_id', 'name', 'phone', 'email',
        'message', 'agreed_to_terms', 'shooting_date', 'status',
        'project_id',
    ];

    protected function casts(): array
    {
        return [
            'agreed_to_terms' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
