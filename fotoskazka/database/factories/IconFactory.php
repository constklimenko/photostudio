<?php

namespace Database\Factories;

use App\Models\Icon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Icon>
 */
class IconFactory extends Factory
{
    protected static ?string $storagePath;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'file_path' => 'icons/'.$name.'.svg',
            'disk' => 'public',
        ];
    }
}
