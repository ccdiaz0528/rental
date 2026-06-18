<?php

namespace Tests\Feature;

use App\Filament\Pages\Reportes;
use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoHistorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('admin');
        $this->user = User::factory()->create(['name' => 'Test User']);
        $this->user->assignRole('user');
    }

    private function createReportes(string $fechaInicio, string $fechaFin): Reportes
    {
        $reportes = new Reportes;
        $reportes->periodo = 'personalizado';
        $reportes->fechaInicio = $fechaInicio;
        $reportes->fechaFin = $fechaFin;

        return $reportes;
    }

    private function createVehiculoConContrato(
        string $placa,
        float $cuotaDiaria = 80000,
        float $admin = 0,
        string $estado = 'activo',
        ?string $fechaInicioContrato = '2026-06-01',
    ): Vehiculo {
        $persona = Persona::create([
            'user_id' => $this->user->id,
            'nombre' => 'Conductor '.$placa,
            'cedula' => 'CC'.substr($placa, -4),
            'tipo' => 'conductor',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->user->id,
            'placa' => $placa,
            'cuota_diaria' => $cuotaDiaria,
            'administracion' => $admin,
            'estado' => $estado,
            'persona_id' => $persona->id,
        ]);

        if ($fechaInicioContrato) {
            Contrato::create([
                'user_id' => $this->user->id,
                'vehiculo_id' => $vehiculo->id,
                'persona_id' => $persona->id,
                'fecha_inicio' => $fechaInicioContrato,
                'valor_diario' => $cuotaDiaria,
                'estado' => 'activo',
            ]);
        }

        return $vehiculo;
    }

    private function createControlDiario(
        Vehiculo $vehiculo,
        string $fecha,
        bool $trabajo = true,
        float $valorGenerado = 0,
        float $gasto = 0,
        ?float $administracion = null,
        ?string $categoriaGasto = null,
    ): ControlDiario {
        $data = [
            'user_id' => $this->user->id,
            'vehiculo_id' => $vehiculo->id,
            'fecha' => $fecha,
            'trabajo' => $trabajo,
            'valor_generado' => $valorGenerado,
            'gasto' => $gasto,
            'categoria_gasto' => $categoriaGasto,
        ];

        if ($administracion !== null) {
            $data['administracion'] = $administracion;
        }

        return ControlDiario::create($data);
    }

    private function createDiasLaborales(Vehiculo $vehiculo, string $startDate, int $days, float $cuotaDiaria): void
    {
        for ($d = 0; $d < $days; $d++) {
            $fecha = Carbon::parse($startDate)->addDays($d)->toDateString();
            $this->createControlDiario($vehiculo, $fecha, true, $cuotaDiaria);
        }
    }

    public function test_get_resumen_basic_calculations(): void
    {
        $this->actingAs($this->user);

        $vehiculo = $this->createVehiculoConContrato('RSUM01', 80000);
        $this->createDiasLaborales($vehiculo, '2026-06-01', 7, 80000);

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $resumen = $reportes->getResumen();

        $this->assertEquals(560000, $resumen['esperado']);
        $this->assertEquals(560000, $resumen['real']);
        $this->assertEquals(0, $resumen['gastos']);
        $this->assertEquals(0, $resumen['administracion']);
        $this->assertEquals(0, $resumen['no_percibido']);
        $this->assertEquals(0, $resumen['diferencia']);
        $this->assertEquals(0, $resumen['dias_no_trabajados']);
        $this->assertEquals(560000, $resumen['neto']);
        $this->assertEquals(7, $resumen['dias']);
        $this->assertEquals(7, $resumen['total_registros_modificados']);
    }

    public function test_get_resumen_with_no_trabajo_days(): void
    {
        $this->actingAs($this->user);

        $vehiculo = $this->createVehiculoConContrato('NOTRB01', 80000);

        $this->createControlDiario($vehiculo, '2026-06-01', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-02', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-03', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-04', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-05', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-06', false, 0);
        $this->createControlDiario($vehiculo, '2026-06-07', false, 0);

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $resumen = $reportes->getResumen();

        $this->assertEquals(560000, $resumen['esperado']);
        $this->assertEquals(400000, $resumen['real']);
        $this->assertEquals(160000, $resumen['no_percibido']);
        $this->assertEquals(2, $resumen['dias_no_trabajados']);
        $this->assertEquals(400000, $resumen['neto']);
    }

    public function test_get_resumen_with_gastos_and_admin(): void
    {
        $this->actingAs($this->user);

        $vehiculo = $this->createVehiculoConContrato('GAST01', 80000, 3000);

        for ($d = 1; $d <= 7; $d++) {
            $this->createControlDiario(
                $vehiculo, "2026-06-$d", true, 80000, 5000, 3000,
            );
        }

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $resumen = $reportes->getResumen();

        $this->assertEquals(560000, $resumen['esperado']);
        $this->assertEquals(560000, $resumen['real']);
        $this->assertEquals(35000, $resumen['gastos']);
        $this->assertEquals(21000, $resumen['administracion']);
        $this->assertEquals(504000, $resumen['neto']);
    }

    public function test_get_resumen_with_vehiculo_historial(): void
    {
        $this->actingAs($this->user);

        $vehiculo = $this->createVehiculoConContrato('HIST01', 80000, 0, 'activo', '2026-06-01');

        $vehiculo->vehiculoHistorial()->delete();

        VehiculoHistorial::create([
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => $vehiculo->persona_id,
            'cuota_diaria' => 80000,
            'administracion' => 0,
            'fecha_inicio' => '2026-06-01 00:00:00',
            'fecha_fin' => '2026-06-04 00:00:00',
        ]);

        VehiculoHistorial::create([
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => $vehiculo->persona_id,
            'cuota_diaria' => 100000,
            'administracion' => 0,
            'fecha_inicio' => '2026-06-04 00:00:00',
            'fecha_fin' => null,
        ]);

        $this->createControlDiario($vehiculo, '2026-06-01', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-02', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-03', true, 80000);
        $this->createControlDiario($vehiculo, '2026-06-04', true, 100000);
        $this->createControlDiario($vehiculo, '2026-06-05', true, 100000);
        $this->createControlDiario($vehiculo, '2026-06-06', true, 100000);
        $this->createControlDiario($vehiculo, '2026-06-07', true, 100000);

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $resumen = $reportes->getResumen();

        $this->assertEquals(640000, $resumen['esperado']);
        $this->assertEquals(640000, $resumen['real']);
        $this->assertEquals(640000, $resumen['neto']);
        $this->assertEquals(0, $resumen['diferencia']);
    }

    public function test_get_detalle_por_vehiculo(): void
    {
        $this->actingAs($this->user);

        $v1 = $this->createVehiculoConContrato('DETV01', 80000);
        $v2 = $this->createVehiculoConContrato('DETV02', 90000);

        for ($d = 1; $d <= 5; $d++) {
            $this->createControlDiario($v1, "2026-06-$d", true, 80000);
        }
        for ($d = 1; $d <= 7; $d++) {
            $this->createControlDiario($v2, "2026-06-$d", true, 90000);
        }

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $detalle = $reportes->getDetallePorVehiculo();

        $this->assertCount(2, $detalle);

        $d1 = collect($detalle)->firstWhere('placa', 'DETV01');
        $d2 = collect($detalle)->firstWhere('placa', 'DETV02');

        $this->assertNotNull($d1);
        $this->assertNotNull($d2);

        $this->assertEquals(560000, $d1['esperado']);
        $this->assertEquals(560000, $d1['real']);
        $this->assertEquals(5, $d1['dias_modificados']);
        $this->assertEquals(80000, $d1['cuota_diaria']);

        $this->assertEquals(630000, $d2['esperado']);
        $this->assertEquals(630000, $d2['real']);
        $this->assertEquals(7, $d2['dias_modificados']);
        $this->assertEquals(90000, $d2['cuota_diaria']);
    }

    public function test_get_detalle_diario(): void
    {
        $this->actingAs($this->user);

        $v1 = $this->createVehiculoConContrato('DIAR01', 80000);
        $v2 = $this->createVehiculoConContrato('DIAR02', 90000);

        for ($d = 1; $d <= 5; $d++) {
            $this->createControlDiario($v1, "2026-06-$d", true, 80000);
            $this->createControlDiario($v2, "2026-06-$d", true, 90000);
        }
        $this->createControlDiario($v2, '2026-06-06', true, 90000);

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $diario = $reportes->getDetalleDiario();

        $this->assertCount(7, $diario);

        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals(170000, $diario[$i]['real']);
            $this->assertEquals(0, $diario[$i]['gastos']);
            $this->assertEquals(0, $diario[$i]['administracion']);
            $this->assertEquals(170000, $diario[$i]['neto']);
        }

        $this->assertEquals(170000, $diario[5]['real']);
        $this->assertEquals(170000, $diario[6]['real']);
    }

    public function test_get_ajustes(): void
    {
        $this->actingAs($this->user);

        $vehiculo = $this->createVehiculoConContrato('AJUS01', 80000);

        $this->createControlDiario($vehiculo, '2026-06-01', true, 85000);
        $this->createControlDiario($vehiculo, '2026-06-02', false, 0);
        $this->createControlDiario($vehiculo, '2026-06-03', true, 80000);

        $reportes = $this->createReportes('2026-06-01', '2026-06-03');
        $ajustes = $reportes->getAjustes();

        $this->assertCount(2, $ajustes);

        $this->assertEquals(80000, $ajustes[0]['esperado']);
        $this->assertEquals(85000, $ajustes[0]['real']);
        $this->assertEquals(5000, $ajustes[0]['diferencia']);
        $this->assertTrue($ajustes[0]['trabajo']);

        $this->assertEquals(80000, $ajustes[1]['esperado']);
        $this->assertEquals(0, $ajustes[1]['real']);
        $this->assertEquals(-80000, $ajustes[1]['diferencia']);
        $this->assertFalse($ajustes[1]['trabajo']);
    }

    public function test_vehiculo_activo_en_fecha(): void
    {
        $this->actingAs($this->user);

        $activo = $this->createVehiculoConContrato('ACTV01', 80000, 0, 'activo');

        $inactivo = $this->createVehiculoConContrato('INACT01', 80000, 0, 'inactivo', '2026-06-01');
        $inactivo->fecha_inactivacion = Carbon::parse('2026-06-05 00:00:00');
        $inactivo->save();

        $activo = Vehiculo::find($activo->id);
        $inactivo = Vehiculo::find($inactivo->id);

        $reportes = $this->createReportes('2026-06-01', '2026-06-10');

        $reflection = new \ReflectionClass($reportes);
        $method = $reflection->getMethod('vehiculoActivoEnFecha');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($reportes, $activo, Carbon::parse('2026-06-01')));
        $this->assertTrue($method->invoke($reportes, $activo, Carbon::parse('2026-06-10')));

        $this->assertTrue($method->invoke($reportes, $inactivo, Carbon::parse('2026-06-01')));
        $this->assertTrue($method->invoke($reportes, $inactivo, Carbon::parse('2026-06-04')));
        $this->assertFalse($method->invoke($reportes, $inactivo, Carbon::parse('2026-06-05')));
        $this->assertFalse($method->invoke($reportes, $inactivo, Carbon::parse('2026-06-06')));
    }

    public function test_get_vehiculos_del_periodo(): void
    {
        $this->actingAs($this->user);

        $this->createVehiculoConContrato('PERD01', 80000, 0, 'activo', '2026-06-01');

        $inactivo = $this->createVehiculoConContrato('PERD02', 80000, 0, 'inactivo', '2026-06-01');
        $inactivo->fecha_inactivacion = Carbon::parse('2026-06-10');
        $inactivo->save();

        $excluido = $this->createVehiculoConContrato('PERD03', 80000, 0, 'inactivo', '2026-05-15');
        $excluido->fecha_inactivacion = Carbon::parse('2026-05-25');
        $excluido->save();

        $reportes = $this->createReportes('2026-06-01', '2026-06-30');

        $reflection = new \ReflectionClass($reportes);
        $method = $reflection->getMethod('getVehiculosDelPeriodo');
        $method->setAccessible(true);
        $vehiculos = $method->invoke($reportes);

        $placas = $vehiculos->pluck('placa')->toArray();
        $this->assertContains('PERD01', $placas);
        $this->assertContains('PERD02', $placas);
        $this->assertNotContains('PERD03', $placas);
    }

    public function test_detalle_por_vehiculo_uses_historial_conductor_name(): void
    {
        $this->actingAs($this->user);

        $personaVieja = Persona::create([
            'user_id' => $this->user->id,
            'nombre' => 'Conductor Viejo',
            'cedula' => 'CC1001',
            'tipo' => 'conductor',
            'estado' => 'activo',
        ]);

        $personaNueva = Persona::create([
            'user_id' => $this->user->id,
            'nombre' => 'Conductor Nuevo',
            'cedula' => 'CC1002',
            'tipo' => 'conductor',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehiculo::create([
            'user_id' => $this->user->id,
            'placa' => 'PERS01',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
            'persona_id' => $personaNueva->id,
        ]);

        Contrato::create([
            'user_id' => $this->user->id,
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => $personaNueva->id,
            'fecha_inicio' => '2026-06-01',
            'valor_diario' => 80000,
            'estado' => 'activo',
        ]);

        $vehiculo->vehiculoHistorial()->delete();

        VehiculoHistorial::create([
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => $personaVieja->id,
            'cuota_diaria' => 80000,
            'administracion' => 0,
            'fecha_inicio' => '2026-06-01 00:00:00',
            'fecha_fin' => '2026-06-04 00:00:00',
        ]);

        VehiculoHistorial::create([
            'vehiculo_id' => $vehiculo->id,
            'persona_id' => $personaNueva->id,
            'cuota_diaria' => 80000,
            'administracion' => 0,
            'fecha_inicio' => '2026-06-04 00:00:00',
            'fecha_fin' => null,
        ]);

        $this->createControlDiario($vehiculo, '2026-06-01', true, 80000);

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $detalle = $reportes->getDetallePorVehiculo();

        $this->assertCount(1, $detalle);
        $this->assertEquals('Conductor Viejo', $detalle[0]['conductor']);
    }

    public function test_get_detalle_diario_empty_when_no_vehicles(): void
    {
        $this->actingAs($this->user);

        $reportes = $this->createReportes('2026-06-01', '2026-06-07');
        $this->assertEmpty($reportes->getDetalleDiario());
    }
}
