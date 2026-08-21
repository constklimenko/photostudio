<?php

namespace Tests\Feature\Filament;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

trait AdminTestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($this->admin);
    }
}
