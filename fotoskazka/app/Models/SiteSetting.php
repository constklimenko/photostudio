<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = Cache::rememberForever('site_settings', fn () => static::query()->pluck('value', 'key')->all());

        $value = trim((string) ($settings[$key] ?? ''));

        return $value === '' ? $default : $value;
    }

    public static function set(string $key, ?string $value): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            static::query()->where('key', $key)->delete();
        } else {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('site_settings');
    }
}
