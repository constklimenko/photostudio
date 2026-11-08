<?php

namespace Database\Factories;

use App\Models\ArContent;
use App\Models\ArTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArContentFactory extends Factory
{
    protected $model = ArContent::class;

    public function definition(): array
    {
        return [
            'ar_target_id' => ArTarget::factory(),
            'file_path' => 'ar/targets/'.fake()->uuid().'/contents/video.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => fake()->numberBetween(100_000, 20_000_000),
        ];
    }
}
