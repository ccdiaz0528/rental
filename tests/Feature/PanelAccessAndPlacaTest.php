<?php

namespace Tests\Feature;

use App\Filament\Resources\Vehiculos\Pages\CreateVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelAccessAndPlacaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
    }

    public function test_users_can_access_panel_outside_local_environment(): void
    {
        config(['app.env' => 'production']);

        foreach (['admin', 'user'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
            $this->actingAs($user)->get('/admin')->assertOk();
        }
    }

    public function test_placa_of_soft_deleted_vehicle_fails_validation_instead_of_db_error(): void
    {
        config(['app.env' => 'local']);
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user);
        Vehiculo::create(['placa' => 'ABC123', 'cuota_diaria' => 50000, 'estado' => 'activo'])->delete();

        Livewire::test(CreateVehiculo::class)
            ->fillForm(['placa' => 'ABC123', 'cuota_diaria' => 50000, 'administracion' => 0, 'estado' => 'activo'])
            ->call('create')
            ->assertHasFormErrors(['placa' => 'unique']);
    }
}
