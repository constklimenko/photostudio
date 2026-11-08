<?php

namespace Database\Factories;

use App\Models\ArTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArTargetFactory extends Factory
{
    protected $model = ArTarget::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'marker_path' => 'ar/targets/'.fake()->uuid().'/marker.jpg',
            'marker_width' => 1600,
            'marker_height' => 1064,
            'tracker_version' => '1.2.5',
        ];
    }
}
