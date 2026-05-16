<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AlertasVencimientos extends BaseWidget
{
    use HasUserContext;

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '300s';

    public function getHeading(): string
    {
        return 'Alertas de vencimientos';
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $adminContext = auth()->user()?->hasRole('admin') ? $this->selectedUserId : null;
        $cacheKey = 'dashboard_vencimientos_v2_'.$userId.'_'.$adminContext;

        $data = Cache::remember($cacheKey, 300, function () {
            $now = now()->toDateString();
            $within30 = now()->addDays(30)->toDateString();

            $query = $this->applyUserScope(Vehiculo::query());

            $soatPorVencer = (clone $query)
                ->whereNotNull('fecha_vencimiento_soat')
                ->whereBetween('fecha_vencimiento_soat', [$now, $within30])
                ->count();

            $soatVencido = (clone $query)
                ->whereNotNull('fecha_vencimiento_soat')
                ->where('fecha_vencimiento_soat', '<', $now)
                ->count();

            $tecnoPorVencer = (clone $query)
                ->whereNotNull('fecha_vencimiento_tecnomecanico')
                ->whereBetween('fecha_vencimiento_tecnomecanico', [$now, $within30])
                ->count();

            $tecnoVencida = (clone $query)
                ->whereNotNull('fecha_vencimiento_tecnomecanico')
                ->where('fecha_vencimiento_tecnomecanico', '<', $now)
                ->count();

            return [
                'soat_por_vencer' => $soatPorVencer,
                'soat_vencido' => $soatVencido,
                'tecno_por_vencer' => $tecnoPorVencer,
                'tecno_vencida' => $tecnoVencida,
            ];
        });

        return [
            Stat::make('SOAT por vencer', $data['soat_por_vencer'])
                ->description('Vence en ≤30 días')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($data['soat_por_vencer'] > 0 ? 'warning' : 'gray'),

            Stat::make('SOAT vencido', $data['soat_vencido'])
                ->description('Vencido')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($data['soat_vencido'] > 0 ? 'danger' : 'gray'),

            Stat::make('Tecnomecánica por vencer', $data['tecno_por_vencer'])
                ->description('Vence en ≤30 días')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($data['tecno_por_vencer'] > 0 ? 'warning' : 'gray'),

            Stat::make('Tecnomecánica vencida', $data['tecno_vencida'])
                ->description('Vencido')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($data['tecno_vencida'] > 0 ? 'danger' : 'gray'),
        ];
    }
}
