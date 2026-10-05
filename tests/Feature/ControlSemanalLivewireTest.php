<?php

namespace Tests\Feature;

use App\Filament\Pages\ControlSemanal;
use App\Models\ControlDiario;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ControlSemanalLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $attacker;

    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('user');
        $this->attacker = User::factory()->create();
        $this->attacker->assignRole('user');

        $this->vehiculo = Vehiculo::create([
            'user_id' => $this->owner->id,
            'placa' => 'OWN001',
            'cuota_diaria' => 80000,
            'estado' => 'activo',
        ]);
    }

    public function test_owner_can_save_registro(): void
    {
        $fecha = now()->toDateString();

        Livewire::actingAs($this->owner)
            ->test(ControlSemanal::class)
            ->call('openRegistroModal', $this->vehiculo->id, $fecha)
            ->set('modalForm.valor_generado', 50000)
            ->call('saveRegistro')
            ->assertHasNoErrors();

        $registro = ControlDiario::withoutGlobalScopes()->where('vehiculo_id', $this->vehiculo->id)->first();
        $this->assertNotNull($registro);
        $this->assertEquals(50000, (float) $registro->valor_generado);
        $this->assertEquals($this->owner->id, $registro->user_id);
    }

    public function test_locked_properties_cannot_be_tampered_with(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($this->attacker)
            ->test(ControlSemanal::class)
            ->set('selectedVehiculoId', $this->vehiculo->id);
    }

    public function test_user_cannot_open_modal_for_foreign_vehicle(): void
    {
        try {
            Livewire::actingAs($this->attacker)
                ->test(ControlSemanal::class)
                ->call('openRegistroModal', $this->vehiculo->id, now()->toDateString());
            $this->fail('Se esperaba ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, ControlDiario::withoutGlobalScopes()->count());
        }
    }

    public function test_save_does_not_write_when_vehicle_is_not_visible_to_user(): void
    {
        $fecha = now()->toDateString();

        $component = Livewire::actingAs($this->owner)
            ->test(ControlSemanal::class)
            ->call('openRegistroModal', $this->vehiculo->id, $fecha)
            ->set('modalForm.valor_generado', 999);

        // El vehículo pasa a otro usuario después de abrir el modal.
        $this->vehiculo->forceFill(['user_id' => $this->attacker->id])->saveQuietly();

        try {
            $component->call('saveRegistro');
            $this->fail('Se esperaba ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, ControlDiario::withoutGlobalScopes()->count());
        }
    }

    public function test_invalid_date_is_rejected(): void
    {
        Livewire::actingAs($this->owner)
            ->test(ControlSemanal::class)
            ->call('openRegistroModal', $this->vehiculo->id, 'no-es-fecha')
            ->assertStatus(422);
    }
}
