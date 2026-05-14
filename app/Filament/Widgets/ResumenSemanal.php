<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardStats;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenSemanal extends BaseWidget
{
    use HasDashboardStats;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Resumen de la semana';
    }

    protected function getStats(): array
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $inicioSemana = now()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $finSemana = $inicioSemana->copy()->addDays(6)->endOfDay();

        $vehiculosActivos = Vehiculo::query()
            ->where('estado', 'activo')
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', auth()->id()))
            ->get(['id', 'cuota_diaria']);

        $registrosSemana = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                ->when(! $isAdmin, fn ($q) => $q->where('control_diarios.user_id', auth()->id()))
                ->get();

        $esperadoSemana = $vehiculosActivos->sum('cuota_diaria') * 7;
        $gastosSemana = 0;
        $realSemana = 0;

        $registrosIndexed = $registrosSemana->keyBy(fn ($r) => $r->vehiculo_id.'-'.$r->fecha->format('Y-m-d'));

        for ($offset = 0; $offset < 7; $offset++) {
            $dia = $inicioSemana->copy()->addDays($offset);
            $key = $dia->format('Y-m-d');

            foreach ($vehiculosActivos as $v) {
                $registro = $registrosIndexed->get($v->id.'-'.$key);
                $gastosSemana += (float) ($registro->gasto ?? 0);

                if ($registro) {
                    $trabajo = $registro->trabajo ?? true;
                    $realSemana += $trabajo ? (float) $registro->valor_generado : 0;
                } else {
                    $realSemana += (float) $v->cuota_diaria;
                }
            }
        }

        $gastosCat = $this->gastosPorCategoria($registrosSemana);

        return [
            Stat::make('Esperado', $this->money($esperadoSemana))
                ->description('7 días de operación')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Neto', $this->money($realSemana - $gastosSemana))
                ->description('Ingreso menos gastos')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color(($realSemana - $gastosSemana) >= 0 ? 'success' : 'danger'),

            Stat::make('Gastos', $this->money($gastosSemana))
                ->description('Total gastos semanales')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('warning'),

            Stat::make('Daño', $this->money($gastosCat['daño']))
                ->description('Daños de la semana')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($gastosCat['daño'] > 0 ? 'danger' : 'gray'),

            Stat::make('Mantenimiento', $this->money($gastosCat['mantenimiento']))
                ->description('Mantenimiento de la semana')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($gastosCat['mantenimiento'] > 0 ? 'info' : 'gray'),

            Stat::make('Multa', $this->money($gastosCat['multa']))
                ->description('Multas de la semana')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($gastosCat['multa'] > 0 ? 'warning' : 'gray'),

            Stat::make('Otro', $this->money($gastosCat['otro']))
                ->description('Otros gastos de la semana')
                ->descriptionIcon('heroicon-o-tag')
                ->color($gastosCat['otro'] > 0 ? 'gray' : 'gray'),
        ];
    }
}
