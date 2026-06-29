<?php

namespace Database\Factories;

use App\Models\ServiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceItem>
 */
class ServiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->word(),
            'is_included' => fake()->boolean(80),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function included(): static
    {
        return $this->state(fn () => ['is_included' => true]);
    }

    public function excluded(): static
    {
        return $this->state(fn () => ['is_included' => false]);
    }
}
