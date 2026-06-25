<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'title' => fake()->word(),
            'alt_text' => fake()->sentence(),
            'disk' => 'public',
            'file_path' => 'images/'.fake()->uuid().'.jpg',
            'thumbnail_path' => 'thumbnails/'.fake()->uuid().'_thumb.webp',
            'mime_type' => 'image/jpeg',
            'width' => fake()->numberBetween(1920, 3840),
            'height' => fake()->numberBetween(1080, 2160),
            'file_size' => fake()->numberBetween(1024, 5120000),
            'collection' => fake()->randomElement(['gallery', 'covers', 'avatars']),
        ];
    }
}
