<?php

namespace App\Filament\Widgets;

use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $hoy = now()->startOfDay();
        $inicioSemana = now()->startOfWeek(Carbon::SUNDAY);
        $finSemana = $inicioSemana->copy()->addDays(6);

        $vehiculosActivos = Vehiculo::query()
            ->where('estado', 'activo')
            ->get(['id', 'cuota_diaria', 'persona_id']);

        $registrosSemana = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                ->get();

        $registrosHoy = $registrosSemana->filter(fn ($r) => $r->fecha->isSameDay($hoy));

        $inicioMes = now()->startOfMonth();
        $registrosMes = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereBetween('fecha', [$inicioMes->toDateString(), now()->toDateString()])
                ->get();

        $resumen = $this->calcularResumen($vehiculosActivos, $registrosSemana, $registrosMes);

        $todos = Vehiculo::all();
        $alertas = $this->getAlertasVencimientos($todos);

        return [
            Stat::make('Ingreso hoy', $this->money($resumen['neto_hoy']))
                ->description('Ingreso real del día')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color($resumen['neto_hoy'] >= 0 ? 'success' : 'danger'),

            Stat::make('Esperado hoy', $this->money($resumen['esperado_hoy']))
                ->description('Cuota base diaria')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Gastos hoy', $this->money($resumen['gastos_hoy']))
                ->description('Gastos del día')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('danger'),

            Stat::make('Esperado semana', $this->money($resumen['esperado_semana']))
                ->description('6 días de operación')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Neto semana', $this->money($resumen['neto_semana']))
                ->description('Ingreso menos gastos')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($resumen['neto_semana'] >= 0 ? 'success' : 'danger'),

            Stat::make('Gastos semana', $this->money($resumen['gastos_semana']))
                ->description('Total semanal')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('warning'),

            Stat::make('Esperado mes', $this->money($resumen['esperado_mes']))
                ->description('Del mes en curso')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Neto mes', $this->money($resumen['neto_mes']))
                ->description('Ingreso menos gastos')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($resumen['neto_mes'] >= 0 ? 'success' : 'danger'),

            Stat::make('Vehículos activos', $vehiculosActivos->count())
                ->description('En operación')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Con conductor', $vehiculosActivos->whereNotNull('persona_id')->count())
                ->description('Asignados')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Contratos activos', Contrato::where('estado', 'activo')->count())
                ->description('Vigentes')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),

            Stat::make('Ajustes semana', $registrosSemana->count())
                ->description('Celdas modificadas')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color($registrosSemana->count() > 0 ? 'warning' : 'gray'),

            Stat::make('SOAT por vencer', $alertas['soat_por_vencer'])
                ->description('Vence en ≤30 días')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($alertas['soat_por_vencer'] > 0 ? 'warning' : 'gray'),

            Stat::make('SOAT vencido', $alertas['soat_vencido'])
                ->description('Vencido')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($alertas['soat_vencido'] > 0 ? 'danger' : 'gray'),

            Stat::make('Tecnomecánica por vencer', $alertas['tecnomecanica_por_vencer'])
                ->description('Vence en ≤30 días')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($alertas['tecnomecanica_por_vencer'] > 0 ? 'warning' : 'gray'),

            Stat::make('Tecnomecánica vencida', $alertas['tecnomecanica_vencida'])
                ->description('Vencido')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($alertas['tecnomecanica_vencida'] > 0 ? 'danger' : 'gray'),
        ];
    }

    private function calcularResumen(Collection $vehiculos, Collection $registrosSemana, Collection $registrosMes): array
    {
        $hoy = now()->startOfDay();
        $inicioSemana = now()->startOfWeek(Carbon::SUNDAY);
        $inicioMes = now()->startOfMonth();

        $esperado_hoy = $vehiculos->sum('cuota_diaria');
        $gastos_hoy = 0;
        $real_hoy = 0;

        foreach ($vehiculos as $v) {
            $registro = $registrosSemana->firstWhere(fn ($r) => $r->fecha->isSameDay($hoy) && $r->vehiculo_id === $v->id);
            if ($registro) {
                $gastos_hoy += (float) ($registro->gasto ?? 0);
                $real_hoy += $registro->trabajo ? (float) $registro->valor_generado : (float) $v->cuota_diaria;
            } else {
                $real_hoy += (float) $v->cuota_diaria;
            }
        }

        $esperado_semana = 0;
        $gastos_semana = 0;
        $real_semana = 0;

        foreach (range(0, 6) as $offset) {
            $dia = $inicioSemana->copy()->addDays($offset);
            $diasCalculado = $this->calcularDia($dia, $vehiculos, $registrosSemana);
            $esperado_semana += $diasCalculado['esperado'];
            $real_semana += $diasCalculado['real'];
            $gastos_semana += $diasCalculado['gastos'];
        }

        $diasMes = now()->day;
        $esperado_mes = $vehiculos->sum('cuota_diaria') * $diasMes;
        $real_mes = 0;
        $gastos_mes = 0;

        foreach ($registrosMes as $registro) {
            $real_mes += $registro->trabajo ? (float) $registro->valor_generado : 0;
            $gastos_mes += (float) ($registro->gasto ?? 0);
        }

        return [
            'esperado_hoy' => $esperado_hoy,
            'real_hoy' => $real_hoy,
            'gastos_hoy' => $gastos_hoy,
            'neto_hoy' => $real_hoy - $gastos_hoy,
            'esperado_semana' => $esperado_semana,
            'real_semana' => $real_semana,
            'gastos_semana' => $gastos_semana,
            'neto_semana' => $real_semana - $gastos_semana,
            'esperado_mes' => $esperado_mes,
            'real_mes' => $real_mes,
            'gastos_mes' => $gastos_mes,
            'neto_mes' => $real_mes - $gastos_mes,
        ];
    }

    private function calcularDia(Carbon $fecha, Collection $vehiculos, Collection $registros): array
    {
        $esperado = 0;
        $real = 0;
        $gastos = 0;

        foreach ($vehiculos as $vehiculo) {
            $registro = $registros->firstWhere(fn ($r) => $r->fecha->isSameDay($fecha) && $r->vehiculo_id === $vehiculo->id);
            $esperado += (float) $vehiculo->cuota_diaria;
            $real += $registro
                ? ($registro->trabajo ? (float) $registro->valor_generado : (float) $vehiculo->cuota_diaria)
                : (float) $vehiculo->cuota_diaria;
            $gastos += (float) ($registro?->gasto ?? 0);
        }

        return ['esperado' => $esperado, 'real' => $real, 'gastos' => $gastos, 'neto' => $real - $gastos];
    }

    private function getAlertasVencimientos(Collection $vehiculos): array
    {
        return [
            'soat_por_vencer' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_soat && now()->diffInDays($v->fecha_vencimiento_soat, false) <= 30 && now()->lte($v->fecha_vencimiento_soat))->count(),
            'soat_vencido' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_soat && now()->gt($v->fecha_vencimiento_soat))->count(),
            'tecnomecanica_por_vencer' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_tecnomecanico && now()->diffInDays($v->fecha_vencimiento_tecnomecanico, false) <= 30 && now()->lte($v->fecha_vencimiento_tecnomecanico))->count(),
            'tecnomecanica_vencida' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_tecnomecanico && now()->gt($v->fecha_vencimiento_tecnomecanico))->count(),
        ];
    }

    private function money(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }
}
