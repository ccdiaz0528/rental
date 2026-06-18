<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoHistorial;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehiculoHistorialTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_historial_created_on_vehicle_creation(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST01',
            'cuota_diaria' => 80000,
            'administracion' => 5000,
            'estado' => 'activo',
        ]);

        $this->assertDatabaseCount('vehiculo_historial', 1);

        $this->assertDatabaseHas('vehiculo_historial', [
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => null,
            'cuota_diaria' => 80000.00,
            'administracion' => 5000.00,
        ]);

        $historial = $vehiculo->vehiculoHistorial()->first();
        $this->assertNotNull($historial->fecha_inicio);
        $this->assertNull($historial->fecha_fin);
    }

    public function test_historial_fecha_inicio_matches_created_at(): void
    {
        $this->actingAs($this->admin);

        $t0 = now();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST01B',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $historial = $vehiculo->vehiculoHistorial()->first();
        $this->assertEquals(
            $vehiculo->created_at->toDateTimeString(),
            $historial->fecha_inicio->toDateTimeString()
        );
    }

    public function test_historial_updated_on_cuota_diaria_change(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST02',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertDatabaseCount('vehiculo_historial', 1);

        sleep(1);
        $vehiculo->update(['cuota_diaria' => 100000]);

        $this->assertDatabaseCount('vehiculo_historial', 2);

        $oldHistorial = $vehiculo->vehiculoHistorial()
            ->where('cuota_diaria', 80000.00)
            ->first();
        $this->assertNotNull($oldHistorial);
        $this->assertNotNull($oldHistorial->fecha_fin);

        $newHistorial = $vehiculo->vehiculoHistorial()
            ->where('cuota_diaria', 100000.00)
            ->first();
        $this->assertNotNull($newHistorial);
        $this->assertNull($newHistorial->fecha_fin);
        $this->assertEquals(100000.0, (float) $newHistorial->cuota_diaria);
    }

    public function test_historial_updated_on_administracion_change(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST03',
            'cuota_diaria' => 80000,
            'administracion' => 5000,
            'estado' => 'activo',
        ]);

        sleep(1);
        $vehiculo->update(['administracion' => 10000]);

        $this->assertDatabaseCount('vehiculo_historial', 2);

        $oldHistorial = $vehiculo->vehiculoHistorial()
            ->where('administracion', 5000.00)
            ->first();
        $this->assertNotNull($oldHistorial);
        $this->assertNotNull($oldHistorial->fecha_fin);

        $newHistorial = $vehiculo->vehiculoHistorial()
            ->where('administracion', 10000.00)
            ->first();
        $this->assertNotNull($newHistorial);
        $this->assertNull($newHistorial->fecha_fin);
        $this->assertEquals(10000.0, (float) $newHistorial->administracion);
    }

    public function test_historial_updated_on_persona_id_change(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST04',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $persona = Persona::create([
            'nombre' => 'Conductor Uno',
            'tipo' => 'conductor',
        ]);

        sleep(1);
        $vehiculo->update(['persona_id' => $persona->id]);

        $this->assertDatabaseCount('vehiculo_historial', 2);

        $oldHistorial = $vehiculo->vehiculoHistorial()
            ->whereNull('persona_id')
            ->first();
        $this->assertNotNull($oldHistorial);
        $this->assertNotNull($oldHistorial->fecha_fin);

        $newHistorial = $vehiculo->vehiculoHistorial()
            ->where('persona_id', $persona->id)
            ->first();
        $this->assertNotNull($newHistorial);
        $this->assertNull($newHistorial->fecha_fin);
    }

    public function test_historial_not_updated_on_unrelated_field_change(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST05',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $vehiculo->update(['placa' => 'TEST05B']);

        $this->assertDatabaseCount('vehiculo_historial', 1);
    }

    public function test_cuota_diaria_en_returns_correct_historical_value(): void
    {
        $this->actingAs($this->admin);

        $t0 = now()->startOfDay();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST06',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $t1 = $t0->copy()->addDay();
        Carbon::setTestNow($t1);
        $vehiculo->update(['cuota_diaria' => 100000]);

        $this->assertEquals(80000.0, $vehiculo->fresh()->cuotaDiariaEn($t0));
        $this->assertEquals(100000.0, $vehiculo->fresh()->cuotaDiariaEn($t1));
        $this->assertEquals(100000.0, $vehiculo->fresh()->cuotaDiariaEn($t1->copy()->addHour()));
    }

    public function test_administracion_en_returns_correct_historical_value(): void
    {
        $this->actingAs($this->admin);

        $t0 = now()->startOfDay();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST07',
            'cuota_diaria' => 80000,
            'administracion' => 5000,
            'estado' => 'activo',
        ]);

        $t1 = $t0->copy()->addDay();
        Carbon::setTestNow($t1);
        $vehiculo->update(['administracion' => 10000]);

        $this->assertEquals(5000.0, $vehiculo->fresh()->administracionEn($t0));
        $this->assertEquals(10000.0, $vehiculo->fresh()->administracionEn($t1));
        $this->assertEquals(10000.0, $vehiculo->fresh()->administracionEn($t1->copy()->addHour()));
    }

    public function test_persona_nombre_en_returns_correct_persona_from_historial(): void
    {
        $this->actingAs($this->admin);

        $personaA = Persona::create(['nombre' => 'Persona A', 'tipo' => 'conductor']);
        $personaB = Persona::create(['nombre' => 'Persona B', 'tipo' => 'conductor']);

        $t0 = now()->startOfDay();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST08',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $t1 = $t0->copy()->addDay();
        Carbon::setTestNow($t1);
        $vehiculo->update(['persona_id' => $personaA->id]);

        $this->assertEquals('Persona A', $vehiculo->fresh()->personaNombreEn($t1));

        $t2 = $t1->copy()->addDay();
        Carbon::setTestNow($t2);
        $vehiculo->fresh()->update(['persona_id' => $personaB->id]);

        $this->assertEquals('Persona B', $vehiculo->fresh()->personaNombreEn($t2));

        $withHistorial = Vehiculo::with('vehiculoHistorial.persona')->find($vehiculo->id);
        $this->assertEquals('Persona A', $withHistorial->personaNombreEn($t1));
        $this->assertEquals('Persona B', $withHistorial->personaNombreEn($t2));
    }

    public function test_concurrent_updates_do_not_break_historial(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST09',
            'cuota_diaria' => 80000,
            'administracion' => 5000,
            'estado' => 'activo',
        ]);

        $vehiculo->update(['cuota_diaria' => 90000]);
        $vehiculo->update(['cuota_diaria' => 100000]);
        $vehiculo->update(['administracion' => 10000]);
        $vehiculo->update(['cuota_diaria' => 75000, 'administracion' => 7500]);

        $this->assertEquals(5, VehiculoHistorial::where('vehiculo_id', $vehiculo->id)->count());

        $openCount = VehiculoHistorial::where('vehiculo_id', $vehiculo->id)
            ->whereNull('fecha_fin')
            ->count();
        $this->assertEquals(1, $openCount);

        $last = VehiculoHistorial::where('vehiculo_id', $vehiculo->id)
            ->whereNull('fecha_fin')
            ->first();
        $this->assertEquals(75000.0, (float) $last->cuota_diaria);
        $this->assertEquals(7500.0, (float) $last->administracion);
    }

    public function test_eager_loaded_collection_path_uses_in_memory_data(): void
    {
        $this->actingAs($this->admin);

        $t0 = now()->startOfDay();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST10',
            'cuota_diaria' => 80000,
            'administracion' => 5000,
            'estado' => 'activo',
        ]);

        $t1 = $t0->copy()->addDay();
        Carbon::setTestNow($t1);
        $vehiculo->update(['cuota_diaria' => 100000]);

        $t2 = $t1->copy()->addDay();
        Carbon::setTestNow($t2);
        $vehiculo->update(['administracion' => 10000]);

        $loaded = Vehiculo::with('vehiculoHistorial')->find($vehiculo->id);
        $this->assertTrue($loaded->relationLoaded('vehiculoHistorial'));
        $this->assertCount(3, $loaded->vehiculoHistorial);

        $this->assertEquals(80000.0, $loaded->cuotaDiariaEn($t0));
        $this->assertEquals(100000.0, $loaded->cuotaDiariaEn($t1));
        $this->assertEquals(100000.0, $loaded->cuotaDiariaEn($t2));
        $this->assertEquals(5000.0, $loaded->administracionEn($t0));
        $this->assertEquals(5000.0, $loaded->administracionEn($t1));
        $this->assertEquals(10000.0, $loaded->administracionEn($t2));
    }

    public function test_simultaneous_cuota_and_administracion_updates_creates_single_historial(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST11',
            'cuota_diaria' => 80000,
            'administracion' => 5000,
            'estado' => 'activo',
        ]);

        sleep(1);
        $vehiculo->update([
            'cuota_diaria' => 100000,
            'administracion' => 10000,
        ]);

        $this->assertDatabaseCount('vehiculo_historial', 2);

        $newHistorial = $vehiculo->vehiculoHistorial()
            ->where('cuota_diaria', 100000.00)
            ->where('administracion', 10000.00)
            ->first();
        $this->assertNotNull($newHistorial);
        $this->assertNull($newHistorial->fecha_fin);

        $oldHistorial = $vehiculo->vehiculoHistorial()
            ->where('cuota_diaria', 80000.00)
            ->where('administracion', 5000.00)
            ->first();
        $this->assertNotNull($oldHistorial);
        $this->assertNotNull($oldHistorial->fecha_fin);
    }

    public function test_administracion_defaults_to_zero_in_historial(): void
    {
        $this->actingAs($this->admin);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST12',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertDatabaseHas('vehiculo_historial', [
            'vehiculo_id' => $vehiculo->id,
            'administracion' => 0.00,
        ]);
    }

    public function test_historial_fecha_fin_is_strictly_greater_boundary(): void
    {
        $this->actingAs($this->admin);

        $t0 = now()->startOfDay();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST13',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $tChange = $t0->copy()->addDay();
        Carbon::setTestNow($tChange);
        $vehiculo->update(['cuota_diaria' => 100000]);

        $this->assertEquals(100000.0, $vehiculo->fresh()->cuotaDiariaEn($tChange));

        $beforeChange = $tChange->copy()->subSecond();
        $this->assertEquals(80000.0, $vehiculo->fresh()->cuotaDiariaEn($beforeChange));
    }

    public function test_persona_nombre_en_returns_null_when_no_conductor_at_that_date(): void
    {
        $this->actingAs($this->admin);

        $persona = Persona::create(['nombre' => 'Driver', 'tipo' => 'conductor']);

        $t0 = now()->startOfDay();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST14',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $t1 = $t0->copy()->addDay();
        Carbon::setTestNow($t1);
        $vehiculo->update(['persona_id' => $persona->id]);

        $this->assertNull($vehiculo->fresh()->personaNombreEn($t0), 'Before assignment should be null');
        $this->assertEquals('Driver', $vehiculo->fresh()->personaNombreEn($t1), 'After assignment should show driver');
    }

    public function test_persona_nombre_en_returns_null_when_historial_persona_deleted(): void
    {
        $this->actingAs($this->admin);

        $persona = Persona::create(['nombre' => 'Deleted Driver', 'tipo' => 'conductor']);

        $t0 = now();
        Carbon::setTestNow($t0);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST15',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $t1 = $t0->copy()->addSecond();
        Carbon::setTestNow($t1);
        $vehiculo->update(['persona_id' => $persona->id]);

        $this->assertEquals('Deleted Driver', $vehiculo->fresh()->personaNombreEn($t1));

        $persona->delete();

        $this->assertNull($vehiculo->fresh()->personaNombreEn($t1), 'After persona deleted should return null');
    }

    public function test_persona_nombre_en_past_date_after_conductor_change(): void
    {
        $this->actingAs($this->admin);

        $personaA = Persona::create(['nombre' => 'Old Driver', 'tipo' => 'conductor']);
        $personaB = Persona::create(['nombre' => 'New Driver', 'tipo' => 'conductor']);

        $t0 = now()->startOfDay();
        Carbon::setTestNow($t0);
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TEST16',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $t1 = $t0->copy()->addDay();
        Carbon::setTestNow($t1);
        $vehiculo->update(['persona_id' => $personaA->id]);

        $tMid = $t1->copy()->addHour();
        Carbon::setTestNow($tMid);

        $t2 = $t1->copy()->addDay();
        Carbon::setTestNow($t2);
        $vehiculo->update(['persona_id' => $personaB->id]);

        $this->assertEquals('Old Driver', $vehiculo->fresh()->personaNombreEn($t1), 'Before assignment shows null');
        $this->assertEquals('Old Driver', $vehiculo->fresh()->personaNombreEn($tMid), 'Mid time still shows old driver');
        $this->assertEquals('New Driver', $vehiculo->fresh()->personaNombreEn($t2), 'After change shows new conductor');
    }
}
