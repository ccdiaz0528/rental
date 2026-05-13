<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonaResourceTest extends TestCase
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

    public function test_admin_sees_all_personas(): void
    {
        Persona::create(['user_id' => $this->admin->id, 'nombre' => 'AdminP', 'tipo' => 'conductor']);
        Persona::create(['user_id' => $this->user->id, 'nombre' => 'UserP', 'tipo' => 'conductor']);

        $this->actingAs($this->admin);
        $this->assertEquals(2, Persona::count());
    }

    public function test_user_sees_only_own_personas(): void
    {
        Persona::create(['user_id' => $this->admin->id, 'nombre' => 'AdminP', 'tipo' => 'conductor']);
        Persona::create(['user_id' => $this->user->id, 'nombre' => 'UserP', 'tipo' => 'conductor']);

        $this->actingAs($this->user);
        $this->assertEquals(1, Persona::count());
    }

    public function test_user_creates_persona_with_own_user_id(): void
    {
        $this->actingAs($this->user);
        $persona = Persona::create([
            'nombre' => 'Pedro Gomez',
            'cedula' => '87654321',
            'telefono' => '3009876543',
            'tipo' => 'propietario',
        ]);

        $this->assertEquals($this->user->id, $persona->user_id);
    }

    public function test_persona_cedula_unique_per_user(): void
    {
        $this->actingAs($this->user);
        Persona::create(['nombre' => 'Uno', 'cedula' => 'AAA', 'tipo' => 'conductor']);

        $this->expectException(QueryException::class);
        Persona::create(['nombre' => 'Dos', 'cedula' => 'AAA', 'tipo' => 'conductor']);
    }

    public function test_admin_can_edit_persona(): void
    {
        $persona = Persona::create([
            'user_id' => $this->admin->id,
            'nombre' => 'Original',
            'cedula' => '111',
            'tipo' => 'conductor',
        ]);

        $this->actingAs($this->admin);
        $persona->nombre = 'Actualizado';
        $persona->save();

        $this->assertDatabaseHas('personas', ['nombre' => 'Actualizado']);
    }

    public function test_admin_can_delete_persona(): void
    {
        $persona = Persona::create([
            'user_id' => $this->admin->id,
            'nombre' => 'Borrable',
            'cedula' => '999',
            'tipo' => 'conductor',
        ]);

        $this->actingAs($this->admin);
        $persona->delete();

        $this->assertDatabaseMissing('personas', ['id' => $persona->id]);
    }
}
