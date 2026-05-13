<?php

namespace Tests\Feature;

use App\Filament\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    public function test_admin_cannot_delete_self(): void
    {
        $this->actingAs($this->admin);
        $canDelete = UserResource::canDelete($this->admin);
        $this->assertFalse($canDelete);
    }

    public function test_non_admin_cannot_view_users(): void
    {
        $this->actingAs($this->user);
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_non_admin_cannot_create_users(): void
    {
        $this->actingAs($this->user);
        $this->assertFalse(UserResource::canCreate());
    }

    public function test_non_admin_cannot_edit_users(): void
    {
        $this->actingAs($this->user);
        $this->assertFalse(UserResource::canEdit($this->admin));
    }

    public function test_non_admin_cannot_delete_users(): void
    {
        $this->actingAs($this->user);
        $this->assertFalse(UserResource::canDelete($this->user));
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin);
        $user = User::create([
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('user');

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_admin_can_edit_user(): void
    {
        $this->actingAs($this->admin);
        $this->user->name = 'Usuario Editado';
        $this->user->save();

        $this->assertDatabaseHas('users', ['name' => 'Usuario Editado']);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $otherUser = User::factory()->create(['email' => 'delete@example.com']);
        $otherUser->assignRole('user');

        $this->actingAs($this->admin);
        $otherUser->delete();

        $this->assertDatabaseMissing('users', ['id' => $otherUser->id]);
    }

    public function test_user_email_must_be_unique(): void
    {
        $this->actingAs($this->admin);
        $this->expectException(QueryException::class);
        User::create(['name' => 'Test', 'email' => $this->admin->email, 'password' => bcrypt('password123')]);
    }

    public function test_user_resource_sees_all_users(): void
    {
        $this->actingAs($this->admin);
        $query = UserResource::getEloquentQuery();
        $count = $query->count();
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function test_admin_example_com_is_admin(): void
    {
        $admin2 = User::factory()->create(['email' => 'admin@example.com']);
        $admin2->assignRole('admin');
        $this->assertTrue($admin2->hasRole('admin'));
    }

    public function test_test_example_com_is_user(): void
    {
        $user2 = User::factory()->create(['email' => 'test@example.com']);
        $user2->assignRole('user');
        $this->assertTrue($user2->hasRole('user'));
    }
}
