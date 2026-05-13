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

    public static function getDefaultMetrics(): array
    {
        return [];
    }

    protected function getStats(): array
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $hoy = $inicioDia = now()->startOfDay();
        $inicioSemana = now()->startOfWeek(Carbon::SUNDAY);
        $finSemana = $inicioSemana->copy()->addDays(6);
        $inicioMes = now()->startOfMonth();

        $vehiculosBase = Vehiculo::query();
        if (! $isAdmin) {
            $vehiculosBase->where('user_id', auth()->id());
        }

        $vehiculosActivos = (clone $vehiculosBase)
            ->where('estado', 'activo')
            ->get(['id', 'cuota_diaria', 'persona_id']);

        $todos = (clone $vehiculosBase)->get(['id', 'fecha_vencimiento_soat', 'fecha_vencimiento_tecnomecanico']);

        $registros = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereBetween('fecha', [$inicioMes->toDateString(), $finSemana->toDateString()])
                ->when(! $isAdmin, fn ($q) => $q->where('control_diarios.user_id', auth()->id()))
                ->get();

        $registrosSemana = $registros->filter(fn ($r) => $r->fecha->between($inicioSemana, $finSemana));
        $registrosMes = $registros->filter(fn ($r) => $r->fecha->between($inicioMes, $hoy));

        $resumen = $this->calcularResumen($vehiculosActivos, $registrosSemana, $registrosMes);

        $contratoStats = Contrato::query()
            ->where('estado', 'activo')
            ->when(! $isAdmin, fn ($q) => $q->where('contratos.user_id', auth()->id()))
            ->selectRaw("SUM(tipo='alquiler') as alquiler, SUM(tipo='opcion_compra') as opcion_compra")
            ->first();

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
                ->description('7 días de operación')
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

            Stat::make('Contratos alquiler', (int) ($contratoStats?->alquiler ?? 0))
                ->description('En alquiler')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Contratos opción compra', (int) ($contratoStats?->opcion_compra ?? 0))
                ->description('Opción de compra')
                ->descriptionIcon('heroicon-o-shopping-cart')
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

        $keyHoY = $hoy->format('Y-m-d');
        $registrosHoyIndexed = $registrosSemana->keyBy(fn ($r) => $r->vehiculo_id.'-'.$r->fecha->format('Y-m-d'));

        $esperado_hoy = $vehiculos->sum('cuota_diaria');
        $gastos_hoy = 0;
        $real_hoy = 0;

        foreach ($vehiculos as $v) {
            $registro = $registrosHoyIndexed->get($v->id.'-'.$keyHoY);
            if ($registro) {
                $gastos_hoy += (float) ($registro->gasto ?? 0);
                $trabajo = $registro->trabajo ?? true;
                $real_hoy += $trabajo ? (float) ($registro->valor_generado ?? $v->cuota_diaria) : 0;
            } else {
                $real_hoy += (float) $v->cuota_diaria;
            }
        }

        $esperado_semana = 0;
        $gastos_semana = 0;
        $real_semana = 0;

        $registrosSemanaIndexed = $registrosSemana->keyBy(fn ($r) => $r->vehiculo_id.'-'.$r->fecha->format('Y-m-d'));

        for ($offset = 0; $offset < 7; $offset++) {
            $dia = $inicioSemana->copy()->addDays($offset);
            $key = $dia->format('Y-m-d');

            foreach ($vehiculos as $v) {
                $esperado_semana += (float) $v->cuota_diaria;
                $registro = $registrosSemanaIndexed->get($v->id.'-'.$key);
                $real_semana += $registro
                    ? ($registro->trabajo ? (float) $registro->valor_generado : (float) $v->cuota_diaria)
                    : (float) $v->cuota_diaria;
                $gastos_semana += (float) ($registro?->gasto ?? 0);
            }
        }

        $diasMes = now()->day;
        $esperado_mes = $vehiculos->sum('cuota_diaria') * $diasMes;
        $real_mes = 0;
        $gastos_mes = 0;

        $registrosMesIndexed = $registrosMes->keyBy(fn ($r) => $r->vehiculo_id.'-'.$r->fecha->format('Y-m-d'));

        foreach ($vehiculos as $v) {
            $cuota = (float) $v->cuota_diaria;
            for ($d = 1; $d <= $diasMes; $d++) {
                $key = $v->id.'-'.$inicioMes->copy()->addDays($d - 1)->format('Y-m-d');
                $registro = $registrosMesIndexed->get($key);
                if ($registro) {
                    $gastos_mes += (float) ($registro->gasto ?? 0);
                    $trabajo = $registro->trabajo ?? true;
                    $real_mes += $trabajo ? (float) ($registro->valor_generado ?? $cuota) : 0;
                } else {
                    $real_mes += $cuota;
                }
            }
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

    private function getAlertasVencimientos(Collection $vehiculos): array
    {
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
            'soat_por_vencer' => $soatPorVencer,
            'soat_vencido' => $soatVencido,
            'tecnomecanica_por_vencer' => $tecnoPorVencer,
            'tecnomecanica_vencida' => $tecnoVencida,
        ];
    }

    private function money(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }
}
