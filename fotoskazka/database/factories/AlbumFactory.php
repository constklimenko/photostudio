<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['portfolio', 'project', 'homepage', 'service', 'client']),
            'is_featured' => fake()->boolean(),
            'is_published' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
