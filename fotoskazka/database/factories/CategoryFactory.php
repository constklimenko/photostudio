<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(),
            'type' => fake()->randomElement(['service', 'post']),
            'sort_order' => fake()->numberBetween(0, 100),
            'parent_id' => null,
            'cover_media_id' => null,
            'is_published' => true,
        ];
    }
}
