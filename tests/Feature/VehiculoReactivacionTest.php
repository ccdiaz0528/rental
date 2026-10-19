<?php

namespace Tests\Feature;

use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiculoReactivacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reactivar_conserva_periodo_inactivo(): void
    {
        $v = new Vehiculo(['estado' => 'activo']);
        $v->fecha_inactivacion = Carbon::parse('2026-10-01');
        $v->estado = 'inactivo';
        $v->periodos_inactivo = [['desde' => '2026-09-01', 'hasta' => '2026-09-15']];

        $this->assertTrue($v->inactivoEn(Carbon::parse('2026-09-10')));
        $this->assertFalse($v->inactivoEn(Carbon::parse('2026-09-15')));
        $this->assertFalse($v->inactivoEn(Carbon::parse('2026-09-30')));
        $this->assertTrue($v->inactivoEn(Carbon::parse('2026-10-05')));

        $v->estado = 'activo';
        $v->fecha_inactivacion = null;
        $this->assertTrue($v->estaBloqueadoEn(Carbon::parse('2026-09-10')));
        $this->assertFalse($v->estaBloqueadoEn(Carbon::parse('2026-10-05')));
    }

    public function test_dia_inactivo_solo_cuenta_si_trabajo(): void
    {
        $v = new Vehiculo(['estado' => 'activo']);
        $v->periodos_inactivo = [['desde' => '2026-10-05', 'hasta' => '2026-10-19']];
        $dia = Carbon::parse('2026-10-18');

        $this->assertFalse($v->cuentaEn($dia, null));
        $this->assertFalse($v->cuentaEn($dia, new ControlDiario(['trabajo' => false, 'gasto' => 0])));
        $this->assertTrue($v->cuentaEn($dia, new ControlDiario(['trabajo' => true, 'gasto' => 0])));
        $this->assertFalse($v->cuentaEn($dia, new ControlDiario(['trabajo' => false, 'gasto' => 20000])));
        $this->assertTrue($v->cuentaEn(Carbon::parse('2026-10-19'), null));
    }
}
