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

class IndicadoresFlota extends BaseWidget
{
    use HasDashboardStats;
    use HasUserContext;

    protected static ?int $sort = 4;

    public function getHeading(): string
    {
        return 'Indicadores de flota';
    }

    protected function getStats(): array
    {
        $inicioSemana = now()->startOfWeek(Carbon::SUNDAY);
        $finSemana = $inicioSemana->copy()->addDays(6);

        $vehiculosActivos = $this->applyUserScope(
            Vehiculo::query()->where('estado', 'activo')
        )->get(['id', 'persona_id']);

        $todosIds = $this->applyUserScope(Vehiculo::query())
            ->pluck('id');

        $ajustesSemana = $vehiculosActivos->isEmpty() || $todosIds->isEmpty()
            ? 0
            : $this->applyUserScope(
                ControlDiario::query()
                    ->whereIn('vehiculo_id', $todosIds)
                    ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
            )->count();

        $contratoStats = $this->applyUserScope(
            Contrato::query()->where('estado', 'activo'),
            'contratos.user_id'
        )->selectRaw("SUM(tipo='alquiler') as alquiler, SUM(tipo='opcion_compra') as opcion_compra")->first();

        return [
            Stat::make('Vehículos activos', $vehiculosActivos->count())
                ->description('En operación')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Con conductor', $vehiculosActivos->whereNotNull('persona_id')->count())
                ->description('Asignados')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Ajustes semana', $ajustesSemana)
                ->description('Celdas modificadas')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color($ajustesSemana > 0 ? 'warning' : 'gray'),

            Stat::make('Contratos alquiler', (int) ($contratoStats?->alquiler ?? 0))
                ->description('En alquiler')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Contratos opción compra', (int) ($contratoStats?->opcion_compra ?? 0))
                ->description('Opción de compra')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('info'),
        ];
    }
}
