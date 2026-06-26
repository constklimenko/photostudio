<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Администратор', 'slug' => 'admin', 'is_system' => true],
            ['name' => 'Фотограф', 'slug' => 'photographer', 'is_system' => true],
            ['name' => 'Клиент', 'slug' => 'client', 'is_system' => true],
            ['name' => 'Родитель', 'slug' => 'parent', 'is_system' => true],
            ['name' => 'Ответственный по классу', 'slug' => 'class_manager', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
