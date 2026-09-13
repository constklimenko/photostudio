<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'type' => fake()->randomElement(['individual', 'family', 'event', 'wedding', 'school', 'kindergarten']),
            'description' => fake()->paragraph(),
            'shooting_date' => fake()->date(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
        ];
    }
}
