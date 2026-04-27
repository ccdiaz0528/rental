<?php

namespace App\Filament\Widgets;

use App\Models\Contrato;
use App\Models\Gasto;
use App\Models\PagoDiario;
use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $hoy = now()->toDateString();
        $mesActual = now()->month;
        $anioActual = now()->year;

        $ingresosHoy = PagoDiario::whereDate('fecha', $hoy)
            ->where('estado', 'pagado')
            ->sum('valor');

        $gastosHoy = Gasto::whereDate('fecha', $hoy)
            ->sum('valor');

        $balanceHoy = $ingresosHoy - $gastosHoy;

        $ingresosMes = PagoDiario::whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->where('estado', 'pagado')
            ->sum('valor');

        $gastosMes = Gasto::whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->sum('valor');

        $balanceMes = $ingresosMes - $gastosMes;

        $vehiculosActivos = Vehiculo::where('estado', 'activo')->count();
        $contratosActivos = Contrato::where('estado', 'activo')->count();
        $pagosPendientes = PagoDiario::where('estado', 'pendiente')->count();

        return [
            Stat::make('Ingresos hoy', '$' . number_format($ingresosHoy, 0, ',', '.'))
                ->description('Pagos recibidos hoy')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Gastos hoy', '$' . number_format($gastosHoy, 0, ',', '.'))
                ->description('Gastos registrados hoy')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger'),

            Stat::make('Balance hoy', '$' . number_format($balanceHoy, 0, ',', '.'))
                ->description('Ingresos menos gastos del día')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($balanceHoy >= 0 ? 'success' : 'danger'),

            Stat::make('Ingresos del mes', '$' . number_format($ingresosMes, 0, ',', '.'))
                ->description('Total recaudado este mes')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Gastos del mes', '$' . number_format($gastosMes, 0, ',', '.'))
                ->description('Total gastado este mes')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('warning'),

            Stat::make('Balance del mes', '$' . number_format($balanceMes, 0, ',', '.'))
                ->description('Utilidad neta del mes')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($balanceMes >= 0 ? 'success' : 'danger'),

            Stat::make('Vehículos activos', $vehiculosActivos)
                ->description('En operación actualmente')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Contratos activos', $contratosActivos)
                ->description('Contratos vigentes')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),

            Stat::make('Pagos pendientes', $pagosPendientes)
                ->description('Conductores con deuda')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($pagosPendientes > 0 ? 'warning' : 'success'),
        ];
    }
}
