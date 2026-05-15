<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AlertasVencimientos extends BaseWidget
{
    use HasUserContext;

    protected static ?int $sort = 5;

    public function getHeading(): string
    {
        return 'Alertas de vencimientos';
    }

    protected function getStats(): array
    {
        $vehiculos = $this->applyUserScope(Vehiculo::query())
            ->get(['id', 'fecha_vencimiento_soat', 'fecha_vencimiento_tecnomecanico']);

        $now = now();
        $soatPorVencer = 0;
        $soatVencido = 0;
        $tecnoPorVencer = 0;
        $tecnoVencida = 0;

        foreach ($vehiculos as $v) {
            if ($v->fecha_vencimiento_soat) {
                $days = $now->diffInDays($v->fecha_vencimiento_soat, false);
                if ($days <= 30 && $days >= 0) {
                    $soatPorVencer++;
                } elseif ($days < 0) {
                    $soatVencido++;
                }
            }
            if ($v->fecha_vencimiento_tecnomecanico) {
                $days = $now->diffInDays($v->fecha_vencimiento_tecnomecanico, false);
                if ($days <= 30 && $days >= 0) {
                    $tecnoPorVencer++;
                } elseif ($days < 0) {
                    $tecnoVencida++;
                }
            }
        }

        return [
            Stat::make('SOAT por vencer', $soatPorVencer)
                ->description('Vence en ≤30 días')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($soatPorVencer > 0 ? 'warning' : 'gray'),

            Stat::make('SOAT vencido', $soatVencido)
                ->description('Vencido')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($soatVencido > 0 ? 'danger' : 'gray'),

            Stat::make('Tecnomecánica por vencer', $tecnoPorVencer)
                ->description('Vence en ≤30 días')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($tecnoPorVencer > 0 ? 'warning' : 'gray'),

            Stat::make('Tecnomecánica vencida', $tecnoVencida)
                ->description('Vencido')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($tecnoVencida > 0 ? 'danger' : 'gray'),
        ];
    }
}
