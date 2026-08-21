<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'short_description' => fake()->paragraph(),
            'description' => fake()->randomHtml(),
            'price_from' => fake()->randomFloat(2, 1000, 100000),
            'is_published' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
