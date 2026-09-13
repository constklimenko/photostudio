<?php

namespace App\Models\Concerns;

use App\Enums\ShootingAlbumDisplay;

trait HasShootingAlbumDisplay
{
    public static function bootHasShootingAlbumDisplay(): void
    {
        static::saving(fn (self $model) => $model->normalizeShootingAlbumDisplay());
    }

    protected function normalizeShootingAlbumDisplay(): void
    {
        if (! $this->shooting_album_id) {
            $this->shooting_album_display = ShootingAlbumDisplay::Card->value;
        }
    }
}
