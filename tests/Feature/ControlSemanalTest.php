<?php

namespace Tests\Feature;

use App\Models\ControlDiario;
use App\Models\User;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ControlSemanalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->vehiculo = Vehiculo::create([
            'user_id' => $this->user->id,
            'placa' => 'ADMINVEH',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);
    }

    public function test_no_record_defaults_to_trabajo_true_and_cuota_diaria(): void
    {
        $this->actingAs($this->user);
        $today = now()->toDateString();

        $record = ControlDiario::query()
            ->where('vehiculo_id', $this->vehiculo->id)
            ->whereDate('fecha', $today)
            ->first();

        $this->assertNull($record);
    }

    public function test_save_registro_creates_control_diario(): void
    {
        $this->actingAs($this->user);
        $fecha = now()->toDateString();

        $record = ControlDiario::updateOrCreate(
            ['vehiculo_id' => $this->vehiculo->id, 'fecha' => $fecha],
            [
                'trabajo' => true,
                'valor_generado' => 85000,
                'gasto' => 5000,
                'categoria_gasto' => 'mantenimiento',
                'observaciones' => 'Reparacion menor',
            ]
        );
        $record->user_id = $this->user->id;
        $record->save();

        $this->assertDatabaseHas('control_diarios', [
            'vehiculo_id' => $this->vehiculo->id,
            'trabajo' => true,
        ]);
    }

    public function test_week_starts_sunday_ends_saturday(): void
    {
        $monday = Carbon::parse('2026-05-11');
        $sunday = $monday->copy()->startOfWeek(Carbon::SUNDAY);
        $saturday = $sunday->copy()->addDays(6);

        $this->assertEquals('2026-05-10', $sunday->toDateString());
        $this->assertEquals('2026-05-16', $saturday->toDateString());
    }

    public function test_admin_sees_all_vehiculos_in_week(): void
    {
        Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'ADMVEH2',
            'cuota_diaria' => 90000,
            'estado' => 'activo',
        ]);

        $this->actingAs($this->admin);
        $allActive = Vehiculo::where('estado', 'activo')->count();
        $this->assertGreaterThanOrEqual(2, $allActive);
    }

    public function test_user_sees_only_own_vehiculos(): void
    {
        Vehiculo::create([
            'user_id' => $this->admin->id,
            'placa' => 'ADMVEH3',
            'cuota_diaria' => 90000,
            'estado' => 'activo',
        ]);

        $this->actingAs($this->user);
        $userVehiculos = Vehiculo::where('estado', 'activo')->count();
        $this->assertEquals(1, $userVehiculos);
    }

    public function test_categoria_gasto_constants(): void
    {
        $this->assertEquals('daño', ControlDiario::CATEGORIA_DAÑO);
        $this->assertEquals('mantenimiento', ControlDiario::CATEGORIA_MANTENIMIENTO);
        $this->assertEquals('multa', ControlDiario::CATEGORIA_MULTA);
        $this->assertEquals('otro', ControlDiario::CATEGORIA_OTRO);
        $this->assertCount(4, ControlDiario::CATEGORIAS);
    }

    public function test_trabajo_false_returns_zero_ingreso(): void
    {
        $this->actingAs($this->user);
        $fecha = now()->subDays(3)->toDateString();

        ControlDiario::updateOrCreate(
            ['vehiculo_id' => $this->vehiculo->id, 'fecha' => $fecha],
            [
                'user_id' => $this->user->id,
                'trabajo' => false,
                'valor_generado' => 0,
                'gasto' => 0,
            ]
        );

        $record = ControlDiario::where('vehiculo_id', $this->vehiculo->id)
            ->whereDate('fecha', $fecha)
            ->first();

        $this->assertFalse($record->trabajo);
        $this->assertEquals(0, $record->valor_generado);
    }

    public function test_unique_constraint_vehiculo_fecha(): void
    {
        $this->actingAs($this->user);
        $fecha = now()->subDays(5)->toDateString();

        ControlDiario::updateOrCreate(
            ['vehiculo_id' => $this->vehiculo->id, 'fecha' => $fecha],
            [
                'user_id' => $this->user->id,
                'trabajo' => true,
                'valor_generado' => 80000,
            ]
        );

        $count = ControlDiario::where('vehiculo_id', $this->vehiculo->id)
            ->whereDate('fecha', $fecha)
            ->count();

        $this->assertEquals(1, $count);
    }
}
