<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Filament\Widgets\Concerns\HasDashboardStats;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ResumenSemanal extends BaseWidget
{
    use HasDashboardStats;
    use HasUserContext;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return 'Resumen de la semana';
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $adminContext = auth()->user()?->hasRole('admin') ? $this->selectedUserId : null;
        $cacheKey = 'dashboard_semanal_v2_'.$userId.'_'.$adminContext;

        $data = Cache::remember($cacheKey, 60, function () {
            $inicioSemana = now()->startOfWeek(Carbon::SUNDAY)->startOfDay();
            $finSemana = $inicioSemana->copy()->addDays(6)->endOfDay();

            $vehiculosActivos = $this->applyUserScope(
                Vehiculo::query()->where('estado', 'activo')
            )->get(['id', 'cuota_diaria']);

            $registrosSemana = $vehiculosActivos->isEmpty()
                ? collect()
                : $this->applyUserScope(
                    ControlDiario::query()
                        ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                        ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                )->get();

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
                'esperado' => $esperadoSemana,
                'neto' => $realSemana - $gastosSemana,
                'gastos' => $gastosSemana,
                'dano' => $gastosCat['daño'],
                'mantenimiento' => $gastosCat['mantenimiento'],
                'multa' => $gastosCat['multa'],
                'otro' => $gastosCat['otro'],
            ];
        });

        return [
            Stat::make('Esperado', $this->money($data['esperado']))
                ->description('7 días de operación')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Neto', $this->money($data['neto']))
                ->description('Ingreso menos gastos')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($data['neto'] >= 0 ? 'success' : 'danger'),

            Stat::make('Gastos', $this->money($data['gastos']))
                ->description('Total gastos semanales')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('warning'),

            Stat::make('Daño', $this->money($data['dano']))
                ->description('Daños de la semana')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($data['dano'] > 0 ? 'danger' : 'gray'),

            Stat::make('Mantenimiento', $this->money($data['mantenimiento']))
                ->description('Mantenimiento de la semana')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($data['mantenimiento'] > 0 ? 'info' : 'gray'),

            Stat::make('Multa', $this->money($data['multa']))
                ->description('Multas de la semana')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($data['multa'] > 0 ? 'warning' : 'gray'),

            Stat::make('Otro', $this->money($data['otro']))
                ->description('Otros gastos de la semana')
                ->descriptionIcon('heroicon-o-tag')
                ->color($data['otro'] > 0 ? 'gray' : 'gray'),
        ];
    }
}
