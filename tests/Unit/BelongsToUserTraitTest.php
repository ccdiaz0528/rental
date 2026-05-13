<?php

namespace Tests\Unit;

use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BelongsToUserTraitTest extends TestCase
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

    public function test_persona_auto_sets_user_id_for_normal_user(): void
    {
        $this->actingAs($this->user);
        $persona = Persona::create([
            'nombre' => 'Auto User',
            'cedula' => 'AUTO001',
            'tipo' => 'conductor',
        ]);

        $this->assertEquals($this->user->id, $persona->user_id);
    }

    public function test_persona_user_id_not_overwritten_if_already_set(): void
    {
        $this->actingAs($this->user);
        $persona = Persona::create([
            'user_id' => $this->admin->id,
            'nombre' => 'PreSet',
            'cedula' => 'PRESET',
            'tipo' => 'conductor',
        ]);

        $this->assertEquals($this->admin->id, $persona->user_id);
    }

    public function test_vehiculo_does_not_have_belongs_to_user_trait(): void
    {
        $this->actingAs($this->user);
        $vehiculo = Vehiculo::create([
            'placa' => 'NOTRAIT',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertNull($vehiculo->user_id);
    }

    public function test_contrato_does_not_have_belongs_to_user_trait(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'CONTTST',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);
        $persona = Persona::create([
            'user_id' => $this->admin->id,
            'nombre' => 'Cond',
            'tipo' => 'conductor',
        ]);

        $this->actingAs($this->user);
        $contrato = Contrato::create([
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => $persona->id,
            'tipo' => 'alquiler',
            'fecha_inicio' => now(),
            'valor_diario' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertNull($contrato->user_id);
    }

    public function test_control_diario_does_not_have_belongs_to_user_trait(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'CTRLTST',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->actingAs($this->user);
        $control = ControlDiario::create([
            'vehiculo_id' => $vehiculo->id,
            'fecha' => now(),
            'trabajo' => true,
            'valor_generado' => 80000,
        ]);

        $this->assertNull($control->user_id);
    }
}
