<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $user = User::create([
            'name' => 'Usuario Normal',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('user');
    }
}
