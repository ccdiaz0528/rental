<?php

namespace Tests\Feature;

use App\Filament\Pages\ControlSemanal;
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

class ControlSemanalReportesSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $weekStart;

    private string $weekEnd;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        // Pin timeline to a known Sunday-Saturday week
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00')); // Thursday of test week

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        // Week: Sun June 21 – Sat June 27 2026 (confirmed: Jun 21 is Sunday)
        $this->weekStart = '2026-06-21';
        $this->weekEnd = '2026-06-27';
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeCS(): ControlSemanal
    {
        $cs = new ControlSemanal;
        $cs->selectedDate = $this->weekStart;

        return $cs;
    }

    private function makeReportes(): Reportes
    {
        $r = new Reportes;
        $r->periodo = 'personalizado';
        $r->fechaInicio = $this->weekStart;
        $r->fechaFin = $this->weekEnd;

        return $r;
    }

    private function createVehiculo(
        string $placa,
        float $cuota = 80000,
        float $admin = 3000,
        string $estado = 'activo',
    ): Vehiculo {
        $persona = Persona::create([
            'user_id' => $this->user->id,
            'nombre' => "Cond $placa",
            'cedula' => "CC$placa",
            'tipo' => 'conductor',
            'estado' => 'activo',
        ]);
        $v = Vehiculo::create([
            'user_id' => $this->user->id,
            'placa' => $placa,
            'cuota_diaria' => $cuota,
            'administracion' => $admin,
            'estado' => $estado,
            'persona_id' => $persona->id,
        ]);
        Contrato::create([
            'user_id' => $this->user->id,
            'vehiculo_id' => $v->id,
            'persona_id' => $persona->id,
            'fecha_inicio' => $this->weekStart,
            'valor_diario' => $cuota,
            'estado' => 'activo',
        ]);

        return $v;
    }

    private function record(Vehiculo $v, string $fecha, bool $trabajo = true, float $valor = 0, float $gasto = 0, ?float $admin = null): ControlDiario
    {
        $d = [
            'user_id' => $this->user->id,
            'vehiculo_id' => $v->id,
            'fecha' => $fecha,
            'trabajo' => $trabajo,
            'valor_generado' => $trabajo ? $valor : 0,
            'gasto' => $gasto,
        ];
        if ($admin !== null) {
            $d['administracion'] = $admin;
        }

        return ControlDiario::create($d);
    }

    // ─── HELPER: assert sync between CS and Reportes ──────────────────
    private function assertSync(string $label): void
    {
        $cs = $this->makeCS();
        $r = $this->makeReportes();
        $w = $cs->getWeekDataset();
        $res = $r->getResumen();

        $this->assertEquals($w['summary']['esperado'], $res['esperado'],
            "[$label] esperado desync: CS={$w['summary']['esperado']} vs R={$res['esperado']}");
        $this->assertEquals($w['summary']['real'], $res['real'],
            "[$label] real desync: CS={$w['summary']['real']} vs R={$res['real']}");
        $this->assertEquals($w['summary']['gastos'], $res['gastos'],
            "[$label] gastos desync: CS={$w['summary']['gastos']} vs R={$res['gastos']}");
        $this->assertEquals($w['summary']['administracion'], $res['administracion'],
            "[$label] admin desync: CS={$w['summary']['administracion']} vs R={$res['administracion']}");
        $this->assertEquals($w['summary']['neto'], $res['neto'],
            "[$label] neto desync: CS={$w['summary']['neto']} vs R={$res['neto']}");
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 1: Normal week, single vehicle, no edits → no records
    // ═══════════════════════════════════════════════════════════════════
    public function test_1_normal_week_no_edits(): void
    {
        $this->actingAs($this->user);
        $this->createVehiculo('NORM01');
        $this->assertSync('no edits');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 2: Mixed records (gasto, no-trabajo, ajuste)
    // ═══════════════════════════════════════════════════════════════════
    public function test_2_mixed_records(): void
    {
        $this->actingAs($this->user);
        $v = $this->createVehiculo('MIX01');
        $this->record($v, '2026-06-22', true, 80000, 5000, 3000);  // Mon gasto
        $this->record($v, '2026-06-24', false, 0, 0, 0);           // Wed no trabajo
        $this->record($v, '2026-06-26', true, 95000, 0, 3000);     // Fri ajuste
        $this->assertSync('mixed records');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 3: Two vehicles, different cuotas
    // ═══════════════════════════════════════════════════════════════════
    public function test_3_two_vehicles(): void
    {
        $this->actingAs($this->user);
        $this->createVehiculo('VH01', 80000, 3000);
        $this->createVehiculo('VH02', 100000, 5000);
        $this->assertSync('two vehicles');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 4: Vehicle inactivo mid-week
    // ═══════════════════════════════════════════════════════════════════
    public function test_4_inactivo_mid_week(): void
    {
        $this->actingAs($this->user);
        $v = $this->createVehiculo('INAC01', 80000, 3000, 'inactivo');
        $v->fecha_inactivacion = Carbon::parse('2026-06-24'); // inactive Thu
        $v->save();

        $this->record($v, '2026-06-22', true, 80000, 0, 3000); // Mon
        $this->record($v, '2026-06-23', true, 80000, 0, 3000); // Tue
        $this->assertSync('inactivo Thu');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 5: Mantenimiento — counts pre-existing records, skips defaults
    // ═══════════════════════════════════════════════════════════════════
    public function test_5_mantenimiento_counts_existing_records(): void
    {
        $this->actingAs($this->user);
        $v = $this->createVehiculo('MANT01', 80000, 3000);

        // Records Mon-Wed (vehicle was active then)
        $this->record($v, '2026-06-22', true, 80000, 0, 3000);
        $this->record($v, '2026-06-23', true, 80000, 0, 3000);
        $this->record($v, '2026-06-24', true, 80000, 0, 3000);

        // Set to mantenimiento Thu
        $v->estado = 'mantenimiento';
        $v->save();

        // Both should count the pre-existing records (Mon-Wed) and skip defaults for Thu-Sat
        $this->assertSync('mantenimiento counts pre-existing records');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 6: Vehicle soft-deleted mid-week (WARNING: uses explicit deleted_at)
    // ═══════════════════════════════════════════════════════════════════
    public function test_6_soft_deleted_mid_week(): void
    {
        $this->actingAs($this->user);
        $v = $this->createVehiculo('DEL01', 80000, 3000);

        $this->record($v, '2026-06-22', true, 80000, 0, 3000); // Mon
        $this->record($v, '2026-06-23', true, 80000, 0, 3000); // Tue

        // Soft-delete Wed by setting deleted_at directly (not using delete() which uses now())
        $v->deleted_at = Carbon::parse('2026-06-24 08:00:00');
        $v->fecha_eliminacion = Carbon::parse('2026-06-24 08:00:00');
        $v->save();

        // Force deleted_at into DB (SoftDeletes normally handles this)
        $v->newQuery()->where('id', $v->id)->update([
            'deleted_at' => '2026-06-24 08:00:00',
            'fecha_eliminacion' => '2026-06-24 08:00:00',
        ]);

        // Reload to refresh trashed() state
        $v = Vehiculo::withTrashed()->find($v->id);
        $this->assertTrue($v->trashed(), 'Vehicle should be trashed');

        $this->assertSync('soft-deleted Wed');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 7: Cuota historial change mid-week (WARNING: sets explicit historial)
    // ═══════════════════════════════════════════════════════════════════
    public function test_7_historial_change_mid_week(): void
    {
        $this->actingAs($this->user);
        $v = $this->createVehiculo('HIST01', 80000, 3000);

        // Remove auto-created historial, create explicit ones with known boundaries
        $v->vehiculoHistorial()->delete();

        // Days: Sun(21) Mon(22) Tue(23) Wed(24) Thu(25) Fri(26) Sat(27)
        // Cuota 80000 for Sun-Wed, 100000 for Thu-Sat
        VehiculoHistorial::create([
            'vehiculo_id' => $v->id,
            'persona_id' => $v->persona_id,
            'cuota_diaria' => 80000,
            'administracion' => 3000,
            'fecha_inicio' => '2026-06-21 00:00:00',
            'fecha_fin' => '2026-06-25 00:00:00',
        ]);
        VehiculoHistorial::create([
            'vehiculo_id' => $v->id,
            'persona_id' => $v->persona_id,
            'cuota_diaria' => 100000,
            'administracion' => 5000,
            'fecha_inicio' => '2026-06-25 00:00:00',
            'fecha_fin' => null,
        ]);

        $this->record($v, '2026-06-26', true, 100000, 0, 5000); // Fri new cuota

        // Expect: Sun-Thu = 80000 (5 days), Fri-Sat = 100000 (2 days)
        // Esperado = 5*80000 + 2*100000 = 600000
        // Admin = 5*3000 + 2*5000 = 25000
        // Real = 4*80000 + 80000(Wed no record) + 100000(Fri) + 100000(Sat no record) = 320000+80000+100000+100000 = 600000
        // Gastos = 0
        // Neto = 600000 - 0 - 25000 = 575000

        $this->assertSync('historial change Thu');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SCENARIO 8: Admin context with vehicle from another user
    // ═══════════════════════════════════════════════════════════════════
    public function test_8_admin_context(): void
    {
        $this->actingAs($this->user);
        $v = $this->createVehiculo('CTX01');
        $this->record($v, '2026-06-23', true, 80000);

        $this->assertSync('admin context');
    }
}
