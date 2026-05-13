<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_role(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $this->assertTrue(Role::where('name', 'admin')->exists());
        $this->assertTrue(Role::where('name', 'user')->exists());
    }

    public function test_seeder_creates_admin_user(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_seeder_creates_normal_user(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $user = User::create([
            'name' => 'Usuario Normal',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('user');

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertTrue($user->hasRole('user'));
    }

    public function test_admin_example_com_has_admin_role(): void
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_test_example_com_has_user_role(): void
    {
        Role::create(['name' => 'user']);
        $user = User::factory()->create(['email' => 'test@example.com']);
        $user->assignRole('user');

        $this->assertTrue($user->hasRole('user'));
    }

    public function test_admin_has_no_user_id(): void
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['name' => 'Admin', 'email' => 'admin2@example.com']);
        $admin->assignRole('admin');

        $this->assertNull($admin->user_id);
    }
}
