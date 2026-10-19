<?php

namespace Tests\Feature;

use App\Filament\Resources\Vehiculos\Pages\EditVehiculo;
use App\Models\ControlDiario;
use App\Models\User;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehiculoReactivacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reactivar_conserva_periodo_inactivo(): void
    {
        $v = new Vehiculo(['estado' => 'activo']);
        $v->created_at = Carbon::parse('2026-01-01');
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

    public function test_dia_inactivo_solo_cuenta_con_trabajo_o_gasto(): void
    {
        $v = new Vehiculo(['estado' => 'activo']);
        $v->created_at = Carbon::parse('2026-01-01');
        $v->periodos_inactivo = [['desde' => '2026-10-05', 'hasta' => '2026-10-19']];
        $dia = Carbon::parse('2026-10-18');

        $this->assertFalse($v->cuentaEn($dia, null));
        $this->assertFalse($v->cuentaEn($dia, new ControlDiario(['trabajo' => false, 'gasto' => 0])));
        $this->assertFalse($v->cuentaEn($dia, new ControlDiario(['trabajo' => true, 'valor_generado' => 0, 'gasto' => 0])));
        $this->assertTrue($v->cuentaEn($dia, new ControlDiario(['trabajo' => true, 'valor_generado' => 30000, 'gasto' => 0])));
        $this->assertTrue($v->cuentaEn($dia, new ControlDiario(['trabajo' => false, 'gasto' => 20000])));
        $this->assertTrue($v->cuentaEn($dia, new ControlDiario(['trabajo' => false, 'gasto' => 0, 'administracion' => 5000])));
        $this->assertTrue($v->cuentaEn(Carbon::parse('2026-10-19'), null));
    }

    public function test_dia_inactivo_con_solo_gasto_no_suma_cuota(): void
    {
        $v = new Vehiculo(['estado' => 'activo']);
        $v->created_at = Carbon::parse('2026-01-01');
        $v->periodos_inactivo = [['desde' => '2026-10-05', 'hasta' => '2026-10-19']];
        $dia = Carbon::parse('2026-10-12');

        $this->assertTrue($v->soloGastoEn($dia, new ControlDiario(['trabajo' => false, 'gasto' => 20000])));
        $this->assertFalse($v->soloGastoEn($dia, new ControlDiario(['trabajo' => true, 'valor_generado' => 30000, 'gasto' => 20000])));
        $this->assertFalse($v->soloGastoEn(Carbon::parse('2026-10-19'), new ControlDiario(['trabajo' => false, 'gasto' => 20000])));
    }

    public function test_formulario_permite_desactivar_con_fecha_anterior_y_editar_periodos(): void
    {
        Role::create(['name' => 'user']);
        $user = User::factory()->create();
        $user->assignRole('user');
        $v = Vehiculo::create(['user_id' => $user->id, 'placa' => 'FORM01', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $hace3 = now()->subDays(3)->startOfDay();

        Livewire::test(EditVehiculo::class, ['record' => $v->getKey()])
            ->fillForm(['estado' => 'inactivo', 'fecha_inactivacion' => $hace3->toDateTimeString()])
            ->call('save')
            ->assertHasNoFormErrors();

        $v->refresh();
        $this->assertSame('inactivo', $v->estado);
        $this->assertSame($hace3->toDateString(), $v->fecha_inactivacion->toDateString());

        Livewire::test(EditVehiculo::class, ['record' => $v->getKey()])
            ->fillForm(['estado' => 'activo'])
            ->call('save')
            ->assertHasNoFormErrors();

        $v->refresh();
        $this->assertNull($v->fecha_inactivacion);
        $this->assertSame($hace3->toDateString(), $v->periodos_inactivo[0]['desde']);
        $this->assertSame(now()->toDateString(), $v->periodos_inactivo[0]['hasta']);

        Livewire::test(EditVehiculo::class, ['record' => $v->getKey()])
            ->fillForm(['periodos_inactivo' => [['desde' => $hace3->toDateString(), 'hasta' => now()->subDay()->toDateString()]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(now()->subDay()->toDateString(), $v->fresh()->periodos_inactivo[0]['hasta']);
    }

    public function test_dia_anterior_al_inicio_cuenta_solo_con_registro_manual_con_actividad(): void
    {
        $v = new Vehiculo(['estado' => 'activo']);
        $v->created_at = Carbon::parse('2026-05-15');
        $antes = Carbon::parse('2026-05-12');

        $this->assertTrue($v->antesDeInicioEn($antes));
        $this->assertFalse($v->cuentaEn($antes, null));
        $this->assertFalse($v->cuentaEn($antes, new ControlDiario(['trabajo' => true, 'valor_generado' => 0])));
        $this->assertTrue($v->cuentaEn($antes, new ControlDiario(['trabajo' => false, 'gasto' => 289000])));
        $this->assertTrue($v->soloGastoEn($antes, new ControlDiario(['trabajo' => false, 'gasto' => 289000])));
        $this->assertFalse($v->soloGastoEn($antes, new ControlDiario(['trabajo' => true, 'valor_generado' => 25000, 'gasto' => 55000])));
        $this->assertTrue($v->cuentaEn(Carbon::parse('2026-05-15'), null));
    }

    public function test_ningun_dia_anterior_a_la_salida_a_produccion_cuenta(): void
    {
        config(['rental.fecha_salida_produccion' => '2026-05-10']);
        $v = new Vehiculo(['estado' => 'activo']);
        $v->created_at = Carbon::parse('2026-01-15');

        $this->assertTrue($v->antesDeInicioEn(Carbon::parse('2026-05-09')));
        $this->assertFalse($v->cuentaEn(Carbon::parse('2026-05-09'), null));
        $this->assertFalse($v->antesDeInicioEn(Carbon::parse('2026-05-10')));
        $this->assertTrue($v->cuentaEn(Carbon::parse('2026-05-10'), null));
    }
}
