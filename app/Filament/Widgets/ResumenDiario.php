<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardStats;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenDiario extends BaseWidget
{
    use HasDashboardStats;

    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Resumen de hoy';
    }

    protected function getStats(): array
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $hoy = now()->startOfDay();

        $vehiculosActivos = Vehiculo::query()
            ->where('estado', 'activo')
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', auth()->id()))
            ->get(['id', 'cuota_diaria']);

        $registrosHoy = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereDate('fecha', $hoy)
                ->when(! $isAdmin, fn ($q) => $q->where('control_diarios.user_id', auth()->id()))
                ->get();

        $esperadoHoy = $vehiculosActivos->sum('cuota_diaria');
        $gastosHoy = 0;
        $realHoy = 0;

        $registrosIndexed = $registrosHoy->keyBy('vehiculo_id');

        foreach ($vehiculosActivos as $v) {
            $registro = $registrosIndexed->get($v->id);
            $gastosHoy += (float) ($registro->gasto ?? 0);

            if ($registro) {
                $trabajo = $registro->trabajo ?? true;
                $realHoy += $trabajo ? (float) $registro->valor_generado : 0;
            } else {
                $realHoy += (float) $v->cuota_diaria;
            }
        }

        $gastosCat = $this->gastosPorCategoria($registrosHoy);

        return [
            Stat::make('Ingreso neto', $this->money($realHoy - $gastosHoy))
                ->description('Ingreso real del día')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color(($realHoy - $gastosHoy) >= 0 ? 'success' : 'danger'),

            Stat::make('Esperado', $this->money($esperadoHoy))
                ->description('Cuota base diaria')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Gastos', $this->money($gastosHoy))
                ->description('Total gastos del día')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('danger'),

            Stat::make('Daño', $this->money($gastosCat['daño']))
                ->description('Daños del día')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($gastosCat['daño'] > 0 ? 'danger' : 'gray'),

            Stat::make('Mantenimiento', $this->money($gastosCat['mantenimiento']))
                ->description('Mantenimiento del día')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($gastosCat['mantenimiento'] > 0 ? 'info' : 'gray'),

            Stat::make('Multa', $this->money($gastosCat['multa']))
                ->description('Multas del día')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($gastosCat['multa'] > 0 ? 'warning' : 'gray'),

            Stat::make('Otro', $this->money($gastosCat['otro']))
                ->description('Otros gastos del día')
                ->descriptionIcon('heroicon-o-tag')
                ->color($gastosCat['otro'] > 0 ? 'gray' : 'gray'),
        ];
    }
}
