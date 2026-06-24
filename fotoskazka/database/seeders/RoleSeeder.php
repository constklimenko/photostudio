<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Администратор', 'slug' => 'admin'],
            ['name' => 'Фотограф', 'slug' => 'photographer'],
            ['name' => 'Клиент', 'slug' => 'client'],
            ['name' => 'Родитель', 'slug' => 'parent'],
            ['name' => 'Ответственный по классу', 'slug' => 'class_manager'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
