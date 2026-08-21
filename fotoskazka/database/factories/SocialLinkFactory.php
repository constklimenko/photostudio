<?php

namespace Database\Factories;

use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialLinkFactory extends Factory
{
    protected $model = SocialLink::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'icon' => fake()->randomElement(['instagram', 'telegram', 'whatsapp', 'vk', 'youtube', 'viber']),
            'url' => fake()->url(),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
