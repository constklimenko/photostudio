<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'media_id' => Media::factory(),
            'caption' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
