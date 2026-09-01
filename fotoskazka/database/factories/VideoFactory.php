<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[a-zA-Z0-9_-]{11}'),
            'type' => 'horizontal',
            'rotate_90' => false,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'show_on_home' => false,
        ];
    }
}
