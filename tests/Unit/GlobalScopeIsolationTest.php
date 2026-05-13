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

class GlobalScopeIsolationTest extends TestCase
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
        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->admin->assignRole('admin');
        $this->userA = User::factory()->create(['name' => 'User A']);
        $this->userA->assignRole('user');
        $this->userB = User::factory()->create(['name' => 'User B']);
        $this->userB->assignRole('user');
    }

    public function test_admin_sees_all_personas(): void
    {
        Persona::create(['user_id' => $this->userA->id, 'nombre' => 'PA', 'tipo' => 'conductor']);
        Persona::create(['user_id' => $this->userB->id, 'nombre' => 'PB', 'tipo' => 'conductor']);

        $this->actingAs($this->admin);
        $count = Persona::count();
        $this->assertEquals(2, $count);
    }

    public function test_user_a_sees_only_own_personas(): void
    {
        Persona::create(['user_id' => $this->userA->id, 'nombre' => 'PA', 'tipo' => 'conductor']);
        Persona::create(['user_id' => $this->userB->id, 'nombre' => 'PB', 'tipo' => 'conductor']);

        $this->actingAs($this->userA);
        $count = Persona::count();
        $this->assertEquals(1, $count);
    }

    public function test_admin_sees_all_vehiculos(): void
    {
        Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'VEHA', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'VEHB', 'cuota_diaria' => 90000, 'estado' => 'activo']);

        $this->actingAs($this->admin);
        $count = Vehiculo::count();
        $this->assertEquals(2, $count);
    }

    public function test_user_a_sees_only_own_vehiculos(): void
    {
        Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'VEHA', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'VEHB', 'cuota_diaria' => 90000, 'estado' => 'activo']);

        $this->actingAs($this->userA);
        $count = Vehiculo::count();
        $this->assertEquals(1, $count);
    }

    public function test_admin_sees_all_contratos(): void
    {
        $vehA = Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'VCA', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $vehB = Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'VCB', 'cuota_diaria' => 90000, 'estado' => 'activo']);
        $perA = Persona::create(['user_id' => $this->userA->id, 'nombre' => 'PCA', 'tipo' => 'conductor']);
        $perB = Persona::create(['user_id' => $this->userB->id, 'nombre' => 'PCB', 'tipo' => 'conductor']);

        Contrato::create(['user_id' => $this->userA->id, 'vehiculo_id' => $vehA->id, 'persona_id' => $perA->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);
        Contrato::create(['user_id' => $this->userB->id, 'vehiculo_id' => $vehB->id, 'persona_id' => $perB->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 90000, 'estado' => 'activo']);

        $this->actingAs($this->admin);
        $count = Contrato::count();
        $this->assertEquals(2, $count);
    }

    public function test_user_a_sees_only_own_contratos(): void
    {
        $vehA = Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'VCA', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $vehB = Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'VCB', 'cuota_diaria' => 90000, 'estado' => 'activo']);
        $perA = Persona::create(['user_id' => $this->userA->id, 'nombre' => 'PCA', 'tipo' => 'conductor']);
        $perB = Persona::create(['user_id' => $this->userB->id, 'nombre' => 'PCB', 'tipo' => 'conductor']);

        Contrato::create(['user_id' => $this->userA->id, 'vehiculo_id' => $vehA->id, 'persona_id' => $perA->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);
        Contrato::create(['user_id' => $this->userB->id, 'vehiculo_id' => $vehB->id, 'persona_id' => $perB->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 90000, 'estado' => 'activo']);

        $this->actingAs($this->userA);
        $count = Contrato::count();
        $this->assertEquals(1, $count);
    }

    public function test_admin_sees_all_control_diarios(): void
    {
        $vehA = Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'CDA', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $vehB = Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'CDB', 'cuota_diaria' => 90000, 'estado' => 'activo']);

        ControlDiario::create(['user_id' => $this->userA->id, 'vehiculo_id' => $vehA->id, 'fecha' => now(), 'trabajo' => true, 'valor_generado' => 80000]);
        ControlDiario::create(['user_id' => $this->userB->id, 'vehiculo_id' => $vehB->id, 'fecha' => now(), 'trabajo' => true, 'valor_generado' => 90000]);

        $this->actingAs($this->admin);
        $count = ControlDiario::count();
        $this->assertEquals(2, $count);
    }

    public function test_user_a_sees_only_own_control_diarios(): void
    {
        $vehA = Vehiculo::create(['user_id' => $this->userA->id, 'placa' => 'CDA', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $vehB = Vehiculo::create(['user_id' => $this->userB->id, 'placa' => 'CDB', 'cuota_diaria' => 90000, 'estado' => 'activo']);

        ControlDiario::create(['user_id' => $this->userA->id, 'vehiculo_id' => $vehA->id, 'fecha' => now(), 'trabajo' => true, 'valor_generado' => 80000]);
        ControlDiario::create(['user_id' => $this->userB->id, 'vehiculo_id' => $vehB->id, 'fecha' => now(), 'trabajo' => true, 'valor_generado' => 90000]);

        $this->actingAs($this->userA);
        $count = ControlDiario::count();
        $this->assertEquals(1, $count);
    }

    public function test_without_global_scope_shows_all(): void
    {
        Persona::create(['user_id' => $this->userA->id, 'nombre' => 'ScopeA', 'tipo' => 'conductor']);
        Persona::create(['user_id' => $this->userB->id, 'nombre' => 'ScopeB', 'tipo' => 'conductor']);

        $this->actingAs($this->userA);
        $all = Persona::withoutGlobalScopes()->count();
        $this->assertEquals(2, $all);
    }

    public function test_admin_can_query_specific_user_records(): void
    {
        Persona::create(['user_id' => $this->userA->id, 'nombre' => 'Specific', 'tipo' => 'conductor']);

        $this->actingAs($this->admin);
        $found = Persona::where('user_id', $this->userA->id)->first();
        $this->assertEquals('Specific', $found->nombre);
    }
}
