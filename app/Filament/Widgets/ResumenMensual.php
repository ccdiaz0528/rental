<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Filament\Widgets\Concerns\HasDashboardStats;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ResumenMensual extends BaseWidget
{
    use HasDashboardStats;
    use HasUserContext;

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return 'Resumen del mes';
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $adminContext = auth()->user()?->hasRole('admin') ? $this->selectedUserId : null;
        $cacheKey = 'dashboard_mensual_v2_'.$userId.'_'.$adminContext;

        $data = Cache::remember($cacheKey, 60, function () {
            $inicioMes = now()->startOfMonth()->startOfDay();
            $hoy = now()->startOfDay();
            $diasMes = now()->day;

            $vehiculosActivos = $this->applyUserScope(
                Vehiculo::query()->where('estado', 'activo')
            )->get(['id', 'cuota_diaria', 'administracion']);

            $registrosMes = $vehiculosActivos->isEmpty()
                ? collect()
                : $this->applyUserScope(
                    ControlDiario::query()
                        ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                        ->whereBetween('fecha', [$inicioMes->toDateString(), $hoy->toDateString()])
                )->get();

            $esperadoMes = $vehiculosActivos->sum('cuota_diaria') * $diasMes;
            $gastosMes = 0;
            $adminMes = 0;
            $realMes = 0;

            $registrosIndexed = $registrosMes->keyBy(fn ($r) => $r->vehiculo_id.'-'.$r->fecha->format('Y-m-d'));

            foreach ($vehiculosActivos as $v) {
                $cuota = (float) $v->cuota_diaria;

                for ($d = 1; $d <= $diasMes; $d++) {
                    $key = $v->id.'-'.$inicioMes->copy()->addDays($d - 1)->format('Y-m-d');
                    $registro = $registrosIndexed->get($key);
                    $gastosMes += (float) ($registro->gasto ?? 0);
                    $adminMes += (float) ($registro->administracion ?? $v->administracion ?? 0);

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
                'esperado' => $esperadoMes,
                'neto' => $realMes - $gastosMes - $adminMes,
                'gastos' => $gastosMes,
                'dano' => $gastosCat['daño'],
                'mantenimiento' => $gastosCat['mantenimiento'],
                'multa' => $gastosCat['multa'],
                'otro' => $gastosCat['otro'],
                'administracion' => $adminMes,
            ];
        });

        return [
            Stat::make('Esperado', $this->money($data['esperado']))
                ->description('Del mes en curso')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Neto', $this->money($data['neto']))
                ->description('Ingreso menos gastos')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($data['neto'] >= 0 ? 'success' : 'danger'),

            Stat::make('Gastos', $this->money($data['gastos']))
                ->description('Total gastos del mes')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('danger'),

            Stat::make('Administración', $this->money($data['administracion']))
                ->description('Costo operativo mensual')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('warning'),

            Stat::make('Daño', $this->money($data['dano']))
                ->description('Daños del mes')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($data['dano'] > 0 ? 'danger' : 'gray'),

            Stat::make('Mantenimiento', $this->money($data['mantenimiento']))
                ->description('Mantenimiento del mes')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($data['mantenimiento'] > 0 ? 'info' : 'gray'),

            Stat::make('Multa', $this->money($data['multa']))
                ->description('Multas del mes')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($data['multa'] > 0 ? 'warning' : 'gray'),

            Stat::make('Otro', $this->money($data['otro']))
                ->description('Otros gastos del mes')
                ->descriptionIcon('heroicon-o-tag')
                ->color($data['otro'] > 0 ? 'gray' : 'gray'),
        ];
    }
}
