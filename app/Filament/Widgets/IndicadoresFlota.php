<?php

namespace App\Filament\Widgets;

use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IndicadoresFlota extends BaseWidget
{
    use Concerns\HasDashboardStats;

    protected static ?int $sort = 4;

    public function getHeading(): string
    {
        return 'Indicadores de flota';
    }

    protected function getStats(): array
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $inicioSemana = now()->startOfWeek(Carbon::SUNDAY);
        $finSemana = $inicioSemana->copy()->addDays(6);

        $vehiculosBase = Vehiculo::query();
        if (! $isAdmin) {
            $vehiculosBase->where('user_id', auth()->id());
        }

        $vehiculosActivos = (clone $vehiculosBase)
            ->where('estado', 'activo')
            ->get(['id', 'persona_id']);

        $todos = (clone $vehiculosBase)->get(['id']);

        $ajustesSemana = $vehiculosActivos->isEmpty() || $todos->isEmpty()
            ? 0
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $todos->pluck('id'))
                ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                ->when(! $isAdmin, fn ($q) => $q->where('control_diarios.user_id', auth()->id()))
                ->count();

        $contratoStats = Contrato::query()
            ->where('estado', 'activo')
            ->when(! $isAdmin, fn ($q) => $q->where('contratos.user_id', auth()->id()))
            ->selectRaw("SUM(tipo='alquiler') as alquiler, SUM(tipo='opcion_compra') as opcion_compra")
            ->first();

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
