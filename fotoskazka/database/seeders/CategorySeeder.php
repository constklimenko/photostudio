<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Выпускные альбомы', 'slug' => 'vypusknye-albomy', 'type' => 'service', 'sort_order' => 1],
            ['name' => 'Детские сады', 'slug' => 'detskie-sady', 'type' => 'service', 'sort_order' => 2],
            ['name' => 'Школьные фотосессии', 'slug' => 'shkolnye-fotosessii', 'type' => 'service', 'sort_order' => 3],
            ['name' => 'Индивидуальные фотосессии', 'slug' => 'individualnye-fotosessii', 'type' => 'service', 'sort_order' => 4],
            ['name' => 'Семейные фотосессии', 'slug' => 'semejnye-fotosessii', 'type' => 'service', 'sort_order' => 5],
            ['name' => 'Съёмка мероприятий', 'slug' => 'syomka-meropriyatij', 'type' => 'service', 'sort_order' => 6],
            ['name' => 'Свадьбы', 'slug' => 'svadby', 'type' => 'service', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug'], 'type' => 'service'],
                $category,
            );
        }
    }
}
