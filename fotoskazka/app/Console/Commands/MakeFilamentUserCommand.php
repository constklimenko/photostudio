<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class MakeFilamentUserCommand extends Command
{
    protected $signature = 'make:filament-user';

    protected $description = 'Create a new Filament admin user';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'status' => 'active',
        ]);

        $role = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Администратор', 'is_system' => true],
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->info("User {$email} created successfully with admin role.");

        return self::SUCCESS;
    }
}
