<?php

namespace App\Enums;

enum ShootingAlbumDisplay: string
{
    case Card = 'card';
    case Grid = 'grid';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Карточка альбома',
            self::Grid => 'Сетка фотографий',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases()),
        );
    }
}
