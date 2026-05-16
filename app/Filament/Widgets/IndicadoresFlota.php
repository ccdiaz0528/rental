<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Filament\Widgets\Concerns\HasDashboardStats;
use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class IndicadoresFlota extends BaseWidget
{
    use HasDashboardStats;
    use HasUserContext;

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '120s';

    public function getHeading(): string
    {
        return 'Indicadores de flota';
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $adminContext = auth()->user()?->hasRole('admin') ? $this->selectedUserId : null;
        $cacheKey = 'dashboard_flota_v2_'.$userId.'_'.$adminContext;

        $data = Cache::remember($cacheKey, 120, function () {
            $inicioSemana = now()->startOfWeek(Carbon::SUNDAY);
            $finSemana = $inicioSemana->copy()->addDays(6);

            $vehiculosQuery = $this->applyUserScope(Vehiculo::query());

            $vehiculosActivos = (clone $vehiculosQuery)
                ->where('estado', 'activo')
                ->get(['id', 'persona_id']);

            $todosIds = (clone $vehiculosQuery)->pluck('id');

            $ajustesSemana = $todosIds->isEmpty()
                ? 0
                : $this->applyUserScope(
                    ControlDiario::query()
                        ->whereIn('vehiculo_id', $todosIds)
                        ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                )->count();

            $contratoStats = $this->applyUserScope(
                Contrato::query()->where('estado', 'activo')
            )->selectRaw("SUM(tipo='alquiler') as alquiler, SUM(tipo='opcion_compra') as opcion_compra")->first();

            return [
                'vehiculos_activos' => $vehiculosActivos->count(),
                'con_conductor' => $vehiculosActivos->whereNotNull('persona_id')->count(),
                'ajustes_semana' => $ajustesSemana,
                'contratos_alquiler' => (int) ($contratoStats?->alquiler ?? 0),
                'contratos_opcion_compra' => (int) ($contratoStats?->opcion_compra ?? 0),
            ];
        });

        return [
            Stat::make('Vehículos activos', $data['vehiculos_activos'])
                ->description('En operación')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Con conductor', $data['con_conductor'])
                ->description('Asignados')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Ajustes semana', $data['ajustes_semana'])
                ->description('Celdas modificadas')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color($data['ajustes_semana'] > 0 ? 'warning' : 'gray'),

            Stat::make('Contratos alquiler', $data['contratos_alquiler'])
                ->description('En alquiler')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Contratos opción compra', $data['contratos_opcion_compra'])
                ->description('Opción de compra')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('info'),
        ];
    }
}
