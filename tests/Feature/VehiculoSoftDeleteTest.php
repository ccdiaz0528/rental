<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehiculoSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_soft_delete_sets_deleted_at(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD001',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $vehiculo->delete();

        $vehiculo->refresh();
        $this->assertNotNull($vehiculo->deleted_at);
        $this->assertNotNull($vehiculo->fecha_eliminacion);
    }

    public function test_soft_delete_preserves_control_diarios(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD002',
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

        $vehiculo->delete();

        $this->assertEquals(1, ControlDiario::count());
        $this->assertDatabaseHas('control_diarios', ['vehiculo_id' => $vehiculo->id]);
    }

    public function test_soft_delete_preserves_contratos(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD003',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $persona = Persona::create([
            'user_id' => $this->admin->id,
            'nombre' => 'Conductor Test',
            'tipo' => 'conductor',
        ]);

        Contrato::create([
            'user_id' => $this->admin->id,
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => $persona->id,
            'tipo' => 'alquiler',
            'fecha_inicio' => now(),
            'valor_diario' => 80000,
            'estado' => 'activo',
        ]);

        $vehiculo->delete();

        $this->assertEquals(1, Contrato::count());
        $this->assertDatabaseHas('contratos', ['vehiculo_id' => $vehiculo->id]);
    }

    public function test_soft_deleted_excluded_from_normal_queries(): void
    {
        Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD004',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $toDelete = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD005',
            'cuota_diaria' => 70000,
            'estado' => 'activo',
        ]);

        $this->assertEquals(2, Vehiculo::count());

        $toDelete->delete();

        $this->assertEquals(1, Vehiculo::count());
        $this->assertEquals(2, Vehiculo::withTrashed()->count());
    }

    public function test_restore_clears_deleted_at_and_sets_restored_at(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD006',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $vehiculo->delete();

        $fecha_eliminacion = $vehiculo->fresh()->fecha_eliminacion;
        $this->assertNotNull($fecha_eliminacion);

        $vehiculo->restore();

        $vehiculo->refresh();
        $this->assertNull($vehiculo->deleted_at);
        $this->assertNotNull($vehiculo->fecha_eliminacion);
        $this->assertNotNull($vehiculo->restored_at);
    }

    public function test_force_delete_removes_permanently(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD007',
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

        $vehiculoId = $vehiculo->id;
        $vehiculo->forceDelete();

        $this->assertDatabaseMissing('vehiculos', ['id' => $vehiculoId]);

        $control = ControlDiario::first();
        $this->assertNotNull($control);
        $this->assertNull($control->vehiculo_id);
    }

    public function test_trashed_vehicle_can_be_deleted_returns_false(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD008',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertTrue($vehiculo->canBeDeleted());

        $vehiculo->delete();
        $this->assertFalse($vehiculo->fresh()->canBeDeleted());

        $vehiculo->restore();
        $this->assertTrue($vehiculo->fresh()->canBeDeleted());
    }

    public function test_deletion_blockers_shows_correct_message_for_trashed(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD009',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertEquals('', $vehiculo->deletionBlockers());

        $vehiculo->delete();
        $this->assertEquals('ya está eliminado', $vehiculo->fresh()->deletionBlockers());
    }

    public function test_multiple_deletes_do_not_overwrite_fecha_eliminacion(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD010',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $vehiculo->delete();
        $firstFechaEliminacion = $vehiculo->fresh()->fecha_eliminacion;
        $this->assertNotNull($firstFechaEliminacion);

        $vehiculo->restore();

        $vehiculo->delete();
        $secondFechaEliminacion = $vehiculo->fresh()->fecha_eliminacion;
        $this->assertNotNull($secondFechaEliminacion);
        $this->assertTrue($secondFechaEliminacion->equalTo($firstFechaEliminacion));
    }

    public function test_restore_makes_vehicle_visible_in_normal_queries(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD011',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD012',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $vehiculo->delete();
        $this->assertEquals(1, Vehiculo::count());

        $vehiculo->restore();
        $this->assertEquals(2, Vehiculo::count());
    }

    public function test_vehiculo_historial_preserved_after_soft_delete(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD013',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertEquals(1, $vehiculo->vehiculoHistorial()->count());

        $vehiculo->delete();
        $this->assertEquals(1, $vehiculo->vehiculoHistorial()->count());
    }

    public function test_with_trashed_scope_includes_all(): void
    {
        $v1 = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD014',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $v2 = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD015',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $v3 = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SD016',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $v1->delete();
        $v3->delete();

        $this->assertEquals(1, Vehiculo::count());
        $this->assertEquals(3, Vehiculo::withTrashed()->count());
        $this->assertEquals(2, Vehiculo::onlyTrashed()->count());
    }
}
