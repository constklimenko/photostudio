<?php

namespace Tests\Unit\Enums;

use App\Enums\ShootingAlbumDisplay;
use PHPUnit\Framework\TestCase;

class ShootingAlbumDisplayTest extends TestCase
{
    public function test_has_exact_cases(): void
    {
        $this->assertSame(['card', 'grid'], array_column(ShootingAlbumDisplay::cases(), 'value'));
    }

    public function test_card_is_default(): void
    {
        $this->assertSame(ShootingAlbumDisplay::Card, ShootingAlbumDisplay::from('card'));
    }

    public function test_labels(): void
    {
        $this->assertSame('Карточка альбома', ShootingAlbumDisplay::Card->label());
        $this->assertSame('Сетка фотографий', ShootingAlbumDisplay::Grid->label());
    }

    public function test_options_are_keyed_by_value_and_labeled(): void
    {
        $options = ShootingAlbumDisplay::options();

        $this->assertSame(count(ShootingAlbumDisplay::cases()), count($options));
        $this->assertSame('Карточка альбома', $options['card']);
        $this->assertSame('Сетка фотографий', $options['grid']);
    }
}
