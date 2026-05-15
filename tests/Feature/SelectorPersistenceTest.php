<?php

namespace Tests\Feature;

use App\Concerns\HasUserContext;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SelectorPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('admin');
        $this->userA = User::factory()->create(['name' => 'Usuario A', 'email' => 'usera@test.com']);
        $this->userA->assignRole('user');
        $this->userB = User::factory()->create(['name' => 'Usuario B', 'email' => 'userb@test.com']);
        $this->userB->assignRole('user');
    }

    public function test_apply_user_scope_filters_by_selected_user(): void
    {
        $this->actingAs($this->admin);

        Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'VEHA', 'cuota_diaria' => 50000, 'estado' => 'activo']);
        Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'VEHB', 'cuota_diaria' => 60000, 'estado' => 'activo']);

        $trait = $this->makeTrait($this->admin, $this->userA->id);

        $vehiculos = Vehiculo::query();
        $scoped = $trait->applyUserScope(clone $vehiculos);

        $this->assertEquals(1, $scoped->count());
        $this->assertEquals('VEHA', $scoped->first()->placa);
    }

    public function test_apply_user_scope_shows_all_when_selected_user_is_zero(): void
    {
        $this->actingAs($this->admin);

        Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'VEHA', 'cuota_diaria' => 50000, 'estado' => 'activo']);
        Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'VEHB', 'cuota_diaria' => 60000, 'estado' => 'activo']);

        $trait = $this->makeTrait($this->admin, 0);

        $vehiculos = Vehiculo::query();
        $scoped = $trait->applyUserScope(clone $vehiculos);

        $this->assertGreaterThanOrEqual(2, $scoped->count());
    }

    public function test_apply_user_scope_scopes_to_auth_user_when_not_admin(): void
    {
        $this->actingAs($this->userA);

        Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'VEHA', 'cuota_diaria' => 50000, 'estado' => 'activo']);
        Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'VEHB', 'cuota_diaria' => 60000, 'estado' => 'activo']);

        $trait = $this->makeTrait($this->userA, 0);

        $vehiculos = Vehiculo::query();
        $scoped = $trait->applyUserScope(clone $vehiculos);

        $this->assertEquals(1, $scoped->count());
        $this->assertEquals('VEHA', $scoped->first()->placa);
    }

    public function test_get_users_for_selector_excludes_admins(): void
    {
        $this->actingAs($this->admin);

        $trait = $this->makeTrait($this->admin, 0);
        $users = $trait->getUsersForSelector();

        $this->assertArrayNotHasKey($this->admin->id, $users);
        $this->assertContains('Usuario A', $users);
        $this->assertContains('Usuario B', $users);
    }

    public function test_get_users_for_selector_returns_empty_for_non_admin(): void
    {
        $this->actingAs($this->userA);

        $trait = $this->makeTrait($this->userA, 0);
        $users = $trait->getUsersForSelector();

        $this->assertEmpty($users);
    }

    public function test_get_selected_user_name_returns_correct_name(): void
    {
        $this->actingAs($this->admin);

        $trait = $this->makeTrait($this->admin, $this->userA->id);
        $name = $trait->getSelectedUserName();

        $this->assertEquals('Usuario A', $name);
    }

    public function test_get_selected_user_name_returns_null_for_all_users(): void
    {
        $this->actingAs($this->admin);

        $trait = $this->makeTrait($this->admin, 0);
        $name = $trait->getSelectedUserName();

        $this->assertNull($name);
    }

    public function test_user_cannot_access_admin_pages(): void
    {
        $this->actingAs($this->userA);

        $this->get('/admin/user/users')->assertForbidden();
    }

    public function test_cache_key_formatted_correctly(): void
    {
        $this->actingAs($this->admin);

        $trait = $this->makeTrait($this->admin, 0);
        $reflection = new \ReflectionClass($trait);
        $method = $reflection->getMethod('userContextCacheKey');
        $method->setAccessible(true);

        $key = $method->invoke($trait);

        $this->assertEquals('admin_user_'.$this->admin->id, $key);
    }

    private function makeTrait(User $user, int $selectedUserId): object
    {
        $this->actingAs($user);

        $trait = new class
        {
            use HasUserContext;
        };
        $reflection = new \ReflectionClass($trait);
        $prop = $reflection->getProperty('selectedUserId');
        $prop->setAccessible(true);
        $prop->setValue($trait, $selectedUserId);

        return $trait;
    }
}
