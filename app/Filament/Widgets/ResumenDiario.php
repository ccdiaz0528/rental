<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Filament\Widgets\Concerns\HasDashboardStats;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ResumenDiario extends BaseWidget
{
    use HasDashboardStats;
    use HasUserContext;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return 'Resumen de hoy';
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $adminContext = auth()->user()?->hasRole('admin') ? $this->selectedUserId : null;
        $cacheKey = 'dashboard_diario_v2_'.$userId.'_'.$adminContext;

        $data = Cache::remember($cacheKey, 60, function () {
            $hoy = now()->startOfDay();

            $vehiculosActivos = $this->applyUserScope(
                Vehiculo::query()->where('estado', 'activo')
            )->get(['id', 'cuota_diaria', 'administracion']);

            $registrosHoy = $vehiculosActivos->isEmpty()
                ? collect()
                : $this->applyUserScope(
                    ControlDiario::query()
                        ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                        ->whereDate('fecha', $hoy)
                )->get();

            $esperadoHoy = $vehiculosActivos->sum('cuota_diaria');
            $gastosHoy = 0;
            $adminHoy = 0;
            $realHoy = 0;

            $registrosIndexed = $registrosHoy->keyBy('vehiculo_id');

            foreach ($vehiculosActivos as $v) {
                $registro = $registrosIndexed->get($v->id);
                $gastosHoy += (float) ($registro->gasto ?? 0);
                $adminHoy += (float) ($registro->administracion ?? $v->administracion ?? 0);

                if ($registro) {
                    $trabajo = $registro->trabajo ?? true;
                    $realHoy += $trabajo ? (float) $registro->valor_generado : 0;
                } else {
                    $realHoy += (float) $v->cuota_diaria;
                }
            }

            $gastosCat = $this->gastosPorCategoria($registrosHoy);

            return [
                'neto' => $realHoy - $gastosHoy - $adminHoy,
                'esperado' => $esperadoHoy,
                'gastos' => $gastosHoy,
                'dano' => $gastosCat['daño'],
                'mantenimiento' => $gastosCat['mantenimiento'],
                'multa' => $gastosCat['multa'],
                'otro' => $gastosCat['otro'],
                'administracion' => $adminHoy,
            ];
        });

        return [
            Stat::make('Ingreso neto', $this->money($data['neto']))
                ->description('Ingreso real del día')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color($data['neto'] >= 0 ? 'success' : 'danger'),

            Stat::make('Esperado', $this->money($data['esperado']))
                ->description('Cuota base diaria')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Gastos', $this->money($data['gastos']))
                ->description('Total gastos del día')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('danger'),

            Stat::make('Daño', $this->money($data['dano']))
                ->description('Daños del día')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($data['dano'] > 0 ? 'danger' : 'gray'),

            Stat::make('Mantenimiento', $this->money($data['mantenimiento']))
                ->description('Mantenimiento del día')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($data['mantenimiento'] > 0 ? 'info' : 'gray'),

            Stat::make('Multa', $this->money($data['multa']))
                ->description('Multas del día')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($data['multa'] > 0 ? 'warning' : 'gray'),

            Stat::make('Otro', $this->money($data['otro']))
                ->description('Otros gastos del día')
                ->descriptionIcon('heroicon-o-tag')
                ->color($data['otro'] > 0 ? 'gray' : 'gray'),
        ];
    }
}
