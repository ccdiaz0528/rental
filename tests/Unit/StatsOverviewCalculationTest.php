<?php

namespace Tests\Unit;

use App\Models\ControlDiario;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StatsOverviewCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_soat_por_vencer_within_30_days(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SOATWARN',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
            'fecha_vencimiento_soat' => now()->addDays(15)->toDateString(),
        ]);

        $alerta = $vehiculo->fecha_vencimiento_soat
            && now()->diffInDays($vehiculo->fecha_vencimiento_soat, false) <= 30
            && now()->lte($vehiculo->fecha_vencimiento_soat);

        $this->assertTrue($alerta);
    }

    public function test_soat_vencido(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SOATEXP',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
            'fecha_vencimiento_soat' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertTrue(now()->gt($vehiculo->fecha_vencimiento_soat));
    }

    public function test_soat_not_alert_when_more_than_30_days(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'SOATOK',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
            'fecha_vencimiento_soat' => now()->addDays(60)->toDateString(),
        ]);

        $alerta = $vehiculo->fecha_vencimiento_soat
            && now()->diffInDays($vehiculo->fecha_vencimiento_soat, false) <= 30
            && now()->lte($vehiculo->fecha_vencimiento_soat);

        $this->assertFalse($alerta);
    }

    public function test_tecnomecanica_por_vencer(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TECNWARN',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
            'fecha_vencimiento_tecnomecanico' => now()->addDays(20)->toDateString(),
        ]);

        $alerta = $vehiculo->fecha_vencimiento_tecnomecanico
            && now()->diffInDays($vehiculo->fecha_vencimiento_tecnomecanico, false) <= 30
            && now()->lte($vehiculo->fecha_vencimiento_tecnomecanico);

        $this->assertTrue($alerta);
    }

    public function test_tecnomecanica_vencida(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'TECNEXP',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
            'fecha_vencimiento_tecnomecanico' => now()->subDays(10)->toDateString(),
        ]);

        $this->assertTrue(now()->gt($vehiculo->fecha_vencimiento_tecnomecanico));
    }

    public function test_no_soat_date_no_alert(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'NOSOAT',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
            'fecha_vencimiento_soat' => null,
        ]);

        $alerta = $vehiculo->fecha_vencimiento_soat
            && now()->diffInDays($vehiculo->fecha_vencimiento_soat, false) <= 30
            && now()->lte($vehiculo->fecha_vencimiento_soat);

        $this->assertFalse($alerta);
    }

    public function test_control_diario_default_worked_uses_cuota(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'DEFCTRL',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        $registro = ControlDiario::where('vehiculo_id', $vehiculo->id)
            ->whereDate('fecha', now()->toDateString())
            ->first();

        $trabajo = $registro?->trabajo ?? true;
        $ingreso = $trabajo ? (float) ($registro?->valor_generado ?? $vehiculo->cuota_diaria) : 0;

        $this->assertTrue($trabajo);
        $this->assertEquals(80000, $ingreso);
    }

    public function test_control_diario_no_trabajo_returns_zero(): void
    {
        $vehiculo = Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'NOTRAB',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);

        ControlDiario::create([
            'user_id' => $this->admin->id,
            'vehiculo_id' => $vehiculo->id,
            'fecha' => now()->toDateString(),
            'trabajo' => false,
            'valor_generado' => 0,
        ]);

        $registro = ControlDiario::where('vehiculo_id', $vehiculo->id)
            ->whereDate('fecha', now()->toDateString())
            ->first();

        $trabajo = $registro?->trabajo ?? true;
        $ingreso = $trabajo ? (float) ($registro?->valor_generado ?? $vehiculo->cuota_diaria) : 0;

        $this->assertFalse($trabajo);
        $this->assertEquals(0, $ingreso);
    }

    public function test_money_formatting(): void
    {
        $formatted = '$'.number_format(1234567.89, 0, ',', '.');
        $this->assertEquals('$1.234.568', $formatted);

        $formattedZero = '$'.number_format(0, 0, ',', '.');
        $this->assertEquals('$0', $formattedZero);
    }

    public function test_neto_calculation(): void
    {
        $valor_generado = 85000;
        $gasto = 5000;
        $neto = $valor_generado - $gasto;
        $this->assertEquals(80000, $neto);
    }
}
