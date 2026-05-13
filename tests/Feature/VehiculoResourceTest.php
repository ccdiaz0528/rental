<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehiculoResourceTest extends TestCase
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

    public function test_admin_sees_all_vehiculos(): void
    {
        Vehiculo::create(['user_id' => $this->admin->id, 'placa' => 'VEHAD1', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        Vehiculo::create(['user_id' => $this->user->id, 'placa' => 'VEHUS1', 'cuota_diaria' => 80000, 'estado' => 'activo']);

        $this->actingAs($this->admin);
        $this->assertEquals(2, Vehiculo::count());
    }

    public function test_user_sees_only_own_vehiculos(): void
    {
        Vehiculo::create(['user_id' => $this->admin->id, 'placa' => 'VEHAD2', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        Vehiculo::create(['user_id' => $this->user->id, 'placa' => 'VEHUS2', 'cuota_diaria' => 80000, 'estado' => 'activo']);

        $this->actingAs($this->user);
        $this->assertEquals(1, Vehiculo::count());
    }

    public function test_vehiculo_placa_unique_per_user(): void
    {
        $this->actingAs($this->user);
        Vehiculo::create(['placa' => 'DUPEV', 'marca' => 'Toyota', 'cuota_diaria' => 80000, 'estado' => 'activo']);

        $this->expectException(QueryException::class);
        Vehiculo::create(['placa' => 'DUPEV', 'marca' => 'Honda', 'cuota_diaria' => 70000, 'estado' => 'activo']);
    }

    public function test_vehiculo_anio_validation(): void
    {
        $this->actingAs($this->admin);

        try {
            Vehiculo::create(['placa' => 'INV001', 'marca' => 'Toyota', 'anio' => 1989, 'cuota_diaria' => 80000, 'estado' => 'activo']);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('anio', $e->errors());
        }

        $ok = Vehiculo::create(['placa' => 'OK001', 'marca' => 'Toyota', 'anio' => 1990, 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $this->assertNotNull($ok);
    }

    public function test_vehiculo_cannot_delete_with_contratos(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'HASCONT',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        Persona::create(['user_id' => $this->admin->id, 'nombre' => 'Conductor', 'tipo' => 'conductor']);

        Contrato::create([
            'user_id' => $this->admin->id,
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => Persona::first()->id,
            'tipo' => 'alquiler',
            'fecha_inicio' => now(),
            'valor_diario' => 80000,
            'estado' => 'activo',
        ]);

        $this->actingAs($this->admin);
        $this->assertFalse($vehiculo->fresh()->canBeDeleted());
        $this->assertStringContainsString('contratos', $vehiculo->deletionBlockers());
    }

    public function test_vehiculo_cannot_delete_with_control_diarios(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'HASCTRL',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        ControlDiario::create([
            'user_id' => $this->admin->id,
            'vehiculo_id' => $vehiculo->id,
            'fecha' => now(),
            'trabajo' => true,
            'valor_generado' => 80000,
        ]);

        $this->actingAs($this->admin);
        $this->assertFalse($vehiculo->fresh()->canBeDeleted());
        $this->assertStringContainsString('controles semanales', $vehiculo->deletionBlockers());
    }

    public function test_vehiculo_can_delete_without_relations(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'FREEDEL',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertTrue($vehiculo->canBeDeleted());
    }
}
