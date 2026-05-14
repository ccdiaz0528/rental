<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardStats;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenMensual extends BaseWidget
{
    use HasDashboardStats;

    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Resumen del mes';
    }

    protected function getStats(): array
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $inicioMes = now()->startOfMonth()->startOfDay();
        $hoy = now()->startOfDay();
        $diasMes = now()->day;

        $vehiculosActivos = Vehiculo::query()
            ->where('estado', 'activo')
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', auth()->id()))
            ->get(['id', 'cuota_diaria']);

        $registrosMes = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereBetween('fecha', [$inicioMes->toDateString(), $hoy->toDateString()])
                ->when(! $isAdmin, fn ($q) => $q->where('control_diarios.user_id', auth()->id()))
                ->get();

        $esperadoMes = $vehiculosActivos->sum('cuota_diaria') * $diasMes;
        $gastosMes = 0;
        $realMes = 0;

        $registrosIndexed = $registrosMes->keyBy(fn ($r) => $r->vehiculo_id.'-'.$r->fecha->format('Y-m-d'));

        foreach ($vehiculosActivos as $v) {
            $cuota = (float) $v->cuota_diaria;

            for ($d = 1; $d <= $diasMes; $d++) {
                $key = $v->id.'-'.$inicioMes->copy()->addDays($d - 1)->format('Y-m-d');
                $registro = $registrosIndexed->get($key);
                $gastosMes += (float) ($registro->gasto ?? 0);

                if ($registro) {
                    $trabajo = $registro->trabajo ?? true;
                    $realMes += $trabajo ? (float) $registro->valor_generado : 0;
                } else {
                    $realMes += $cuota;
                }
            }
        }

        $gastosCat = $this->gastosPorCategoria($registrosMes);

        return [
            Stat::make('Esperado', $this->money($esperadoMes))
                ->description('Del mes en curso')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Neto', $this->money($realMes - $gastosMes))
                ->description('Ingreso menos gastos')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color(($realMes - $gastosMes) >= 0 ? 'success' : 'danger'),

            Stat::make('Gastos', $this->money($gastosMes))
                ->description('Total gastos del mes')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('danger'),

            Stat::make('Daño', $this->money($gastosCat['daño']))
                ->description('Daños del mes')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($gastosCat['daño'] > 0 ? 'danger' : 'gray'),

            Stat::make('Mantenimiento', $this->money($gastosCat['mantenimiento']))
                ->description('Mantenimiento del mes')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($gastosCat['mantenimiento'] > 0 ? 'info' : 'gray'),

            Stat::make('Multa', $this->money($gastosCat['multa']))
                ->description('Multas del mes')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($gastosCat['multa'] > 0 ? 'warning' : 'gray'),

            Stat::make('Otro', $this->money($gastosCat['otro']))
                ->description('Otros gastos del mes')
                ->descriptionIcon('heroicon-o-tag')
                ->color($gastosCat['otro'] > 0 ? 'gray' : 'gray'),
        ];
    }
}
