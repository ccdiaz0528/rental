<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContratoResourceTest extends TestCase
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

    public function test_admin_sees_all_contratos(): void
    {
        $vehA = Vehiculo::create(['user_id' => $this->admin->id, 'placa' => 'COA1', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $vehB = Vehiculo::create(['user_id' => $this->user->id, 'placa' => 'COB1', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $perA = Persona::create(['user_id' => $this->admin->id, 'nombre' => 'PCA1', 'tipo' => 'conductor']);
        $perB = Persona::create(['user_id' => $this->user->id, 'nombre' => 'PCB1', 'tipo' => 'conductor']);

        Contrato::create(['user_id' => $this->admin->id, 'vehiculo_id' => $vehA->id, 'persona_id' => $perA->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);
        Contrato::create(['user_id' => $this->user->id, 'vehiculo_id' => $vehB->id, 'persona_id' => $perB->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);

        $this->actingAs($this->admin);
        $this->assertEquals(2, Contrato::count());
    }

    public function test_user_sees_only_own_contratos(): void
    {
        $vehA = Vehiculo::create(['user_id' => $this->admin->id, 'placa' => 'COA2', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $vehB = Vehiculo::create(['user_id' => $this->user->id, 'placa' => 'COB2', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $perA = Persona::create(['user_id' => $this->admin->id, 'nombre' => 'PCA2', 'tipo' => 'conductor']);
        $perB = Persona::create(['user_id' => $this->user->id, 'nombre' => 'PCB2', 'tipo' => 'conductor']);

        Contrato::create(['user_id' => $this->admin->id, 'vehiculo_id' => $vehA->id, 'persona_id' => $perA->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);
        Contrato::create(['user_id' => $this->user->id, 'vehiculo_id' => $vehB->id, 'persona_id' => $perB->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);

        $this->actingAs($this->user);
        $this->assertEquals(1, Contrato::count());
    }

    public function test_contrato_requires_vehiculo_and_persona(): void
    {
        $this->actingAs($this->admin);
        $this->expectException(QueryException::class);
        Contrato::create(['tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);
    }

    public function test_contrato_tipo_options(): void
    {
        $veh = Vehiculo::create(['user_id' => $this->admin->id, 'placa' => 'TIPO1', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $per = Persona::create(['user_id' => $this->admin->id, 'nombre' => 'TPNM', 'tipo' => 'conductor']);

        $alquiler = Contrato::create(['user_id' => $this->admin->id, 'vehiculo_id' => $veh->id, 'persona_id' => $per->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);
        $opcion = Contrato::create(['user_id' => $this->admin->id, 'vehiculo_id' => $veh->id, 'persona_id' => $per->id, 'tipo' => 'opcion_compra', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);

        $this->assertEquals('alquiler', $alquiler->tipo);
        $this->assertEquals('opcion_compra', $opcion->tipo);
    }

    public function test_contrato_fecha_fin_nullable(): void
    {
        $veh = Vehiculo::create(['user_id' => $this->admin->id, 'placa' => 'FNUL1', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $per = Persona::create(['user_id' => $this->admin->id, 'nombre' => 'FNP', 'tipo' => 'conductor']);

        $contrato = Contrato::create(['user_id' => $this->admin->id, 'vehiculo_id' => $veh->id, 'persona_id' => $per->id, 'tipo' => 'alquiler', 'fecha_inicio' => now(), 'valor_diario' => 80000, 'estado' => 'activo']);
        $this->assertNull($contrato->fecha_fin);
    }

    public function test_contrato_documento_upload(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf');

        $veh = Vehiculo::create(['user_id' => $this->admin->id, 'placa' => 'DOC1', 'cuota_diaria' => 80000, 'estado' => 'activo']);
        $per = Persona::create(['user_id' => $this->admin->id, 'nombre' => 'DCP', 'tipo' => 'conductor']);

        $this->actingAs($this->admin);
        $contrato = Contrato::create([
            'user_id' => $this->admin->id,
            'vehiculo_id' => $veh->id,
            'persona_id' => $per->id,
            'tipo' => 'alquiler',
            'fecha_inicio' => now(),
            'valor_diario' => 80000,
            'estado' => 'activo',
        ]);

        $this->assertNotNull($contrato);
    }
}
