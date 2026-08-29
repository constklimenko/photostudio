<?php

namespace App\Models;

use Database\Factories\IconFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Icon extends Model
{
    /** @use HasFactory<IconFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'file_path', 'disk',
    ];

    public function serviceItems(): HasMany
    {
        return $this->hasMany(ServiceItem::class);
    }

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }
}
