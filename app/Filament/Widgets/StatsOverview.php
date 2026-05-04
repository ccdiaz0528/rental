<?php

namespace App\Filament\Widgets;

use App\Models\Contrato;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

/**
 * Widget de estadísticas del escritorio (Dashboard).
 * 
 * Este widget muestra un resumen visual de las métricas más importantes
 * del sistema de rental de vehículos:
 * - Estadísticas diarias: ingreso de hoy, esperado, gastos
 * - Estadísticas semanales: esperado, neto, gastos
 * - Estadísticas mensuales: esperado, neto
 * - Información de flota: vehículos activos, conductor asignado, contratos
 * - Alertas de documentos: SOAT y tecnomecánica por vencer/vencidos
 * 
 * Se muestra en el panel de administración al iniciar sesión.
 */
class StatsOverview extends BaseWidget
{
    /**
     * Orden de aparición en el escritorio (1 = primero).
     * @var int|null
     */
    protected static ?int $sort = 1;

    /**
     * Genera las estadísticas a mostrar en el widget.
     * 
     * Este método consulta:
     * 1. Vehículos activos del sistema
     * 2. Registros de control diario de la semana actual
     * 3. Registros de control diario del mes actual
     * 4. Contratos activos
     * 5. Fechas de vencimiento de SOAT y tecnomecánica
     * 
     * Calcula totales y neto (ingreso - gastos) para diferentes períodos.
     *
     * @return array<Stat> Array de estadísticas para mostrar
     */
    protected function getStats(): array
    {
        // Obtener fechas actuales
        $hoy = now()->startOfDay();
        $inicioSemana = now()->startOfWeek(Carbon::SUNDAY);
        $finSemana = $inicioSemana->copy()->addDays(6);

        // Obtener vehículos activos
        $vehiculosActivos = Vehiculo::query()
            ->where('estado', 'activo')
            ->get(['id', 'cuota_diaria', 'persona_id']);

        // Obtener registros de control diario de la semana actual
        $registrosSemana = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                ->get();

        // Filtrar registros de hoy
        $registrosHoy = $registrosSemana->filter(fn ($r) => $r->fecha->isSameDay($hoy));

        // Obtener registros del mes actual
        $inicioMes = now()->startOfMonth();
        $registrosMes = $vehiculosActivos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculosActivos->pluck('id'))
                ->whereBetween('fecha', [$inicioMes->toDateString(), now()->toDateString()])
                ->get();

        // Calcular resumen de ingresos/gastos
        $resumen = $this->calcularResumen($vehiculosActivos, $registrosSemana, $registrosMes);

        // Obtener alertas de vencimientos
        $todos = Vehiculo::all();
        $alertas = $this->getAlertasVencimientos($todos);

        return [
            // Estructura: Stat::make('Etiqueta', 'Valor')->description('Descripción')->descriptionIcon('icono')->color('color')

            // Estadísticas de HOY
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

            // Estadísticas de la SEMANA
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

            // Estadísticas del MES
            Stat::make('Esperado mes', $this->money($resumen['esperado_mes']))
                ->description('Del mes en curso')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Neto mes', $this->money($resumen['neto_mes']))
                ->description('Ingreso menos gastos')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($resumen['neto_mes'] >= 0 ? 'success' : 'danger'),

            // Información de la FLOTA
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

            // Alertas de SOAT
            Stat::make('SOAT por vencer', $alertas['soat_por_vencer'])
                ->description('Vence en ≤30 días')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($alertas['soat_por_vencer'] > 0 ? 'warning' : 'gray'),

            Stat::make('SOAT vencido', $alertas['soat_vencido'])
                ->description('Vencido')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($alertas['soat_vencido'] > 0 ? 'danger' : 'gray'),

            // Alertas de Tecnomecánica
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

    /**
     * Calcula el resumen de ingresos, gastos y netos para diferentes períodos.
     * 
     * Este método procesa los vehículos activos y sus registros de control diario
     * para calcular:
     * - Hoy: esperado, real, gastos, neto
     * - Semana: esperado, real, gastos, neto
     * - Mes: esperado, real, gastos, neto
     *
     * @param Collection $vehiculos - Colección de vehículos activos
     * @param Collection $registrosSemana - Registros de control diario de la semana
     * @param Collection $registrosMes - Registros de control diario del mes
     * @return array Array con todas las métricas calculadas
     */
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
                // Si trabajó: usar valor_generado; si no trabajó: 0; si no hay registro: usar cuota_diaria (por defecto trabajó)
                $trabajo = $registro->trabajo ?? true;
                $real_hoy += $trabajo ? (float) ($registro->valor_generado ?? $v->cuota_diaria) : 0;
            } else {
                // No hay registro: usar valores por defecto (trabajo=true, cuota_diaria)
                $real_hoy += (float) $v->cuota_diaria;
            }
        }

        // Calcular métricas de la SEMANA (domingo a sábado = 7 días)
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

        // Calcular métricas del MES
        $diasMes = now()->day;
        $esperado_mes = $vehiculos->sum('cuota_diaria') * $diasMes;
        $real_mes = 0;
        $gastos_mes = 0;

        // Calcular real_mes considerando valores por defecto para días sin registro
        $inicioMes = now()->startOfMonth();
        $hoy = now()->startOfDay();
        
        foreach ($vehiculos as $v) {
            for ($d = 1; $d <= $diasMes; $d++) {
                $fecha = $inicioMes->copy()->addDays($d - 1);
                if ($fecha->isAfter($hoy)) {
                    break;
                }
                $registro = $registrosMes->firstWhere(fn ($r) => $r->fecha->isSameDay($fecha) && $r->vehiculo_id === $v->id);
                
                if ($registro) {
                    $gastos_mes += (float) ($registro->gasto ?? 0);
                    $trabajo = $registro->trabajo ?? true;
                    $real_mes += $trabajo ? (float) ($registro->valor_generado ?? $v->cuota_diaria) : 0;
                } else {
                    // Valores por defecto
                    $real_mes += (float) $v->cuota_diaria;
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

    /**
     * Calcula las métricas (esperado, real, gastos) para un día específico.
     * 
     * @param Carbon $fecha - Fecha a calcular
     * @param Collection $vehiculos - Vehículos activos
     * @param Collection $registros - Registros de control diario
     * @return array Métricas del día: esperado, real, gastos, neto
     */
    private function calcularDia(Carbon $fecha, Collection $vehiculos, Collection $registros): array
    {
        $esperado = 0;
        $real = 0;
        $gastos = 0;

        foreach ($vehiculos as $vehiculo) {
            // Buscar registro para este vehículo en esta fecha
            $registro = $registros->firstWhere(fn ($r) => $r->fecha->isSameDay($fecha) && $r->vehiculo_id === $vehiculo->id);
            
            // Sumar esperado (siempre es la cuota_diaria)
            $esperado += (float) $vehiculo->cuota_diaria;
            
            // Calcular real: si hay registro y trabajó, usar valor_generado; si no trabajó, usar 0; si no hay registro, usar cuota_diaria
            $real += $registro
                ? ($registro->trabajo ? (float) $registro->valor_generado : (float) $vehiculo->cuota_diaria)
                : (float) $vehiculo->cuota_diaria;
            
            // Sumar gastos del registro o 0
            $gastos += (float) ($registro?->gasto ?? 0);
        }

        return ['esperado' => $esperado, 'real' => $real, 'gastos' => $gastos, 'neto' => $real - $gastos];
    }

    /**
     * Obtiene alertas de vehículos con documentos por vencer o vencidos.
     * 
     * @param Collection $vehiculos - Todos los vehículos
     * @return array Array con conteos de: soat_por_vencer, soat_vencido, tecnomecanica_por_vencer, tecnomecanica_vencida
     */
    private function getAlertasVencimientos(Collection $vehiculos): array
    {
        return [
            // SOAT: vence en los próximos 30 días y aún no ha vencido
            'soat_por_vencer' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_soat && now()->diffInDays($v->fecha_vencimiento_soat, false) <= 30 && now()->lte($v->fecha_vencimiento_soat))->count(),
            // SOAT: ya pasó la fecha de vencimiento
            'soat_vencido' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_soat && now()->gt($v->fecha_vencimiento_soat))->count(),
            // Tecnomecánica: vence en los próximos 30 días
            'tecnomecanica_por_vencer' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_tecnomecanico && now()->diffInDays($v->fecha_vencimiento_tecnomecanico, false) <= 30 && now()->lte($v->fecha_vencimiento_tecnomecanico))->count(),
            // Tecnomecánica: ya pasó la fecha de vencimiento
            'tecnomecanica_vencida' => $vehiculos->filter(fn ($v) => $v->fecha_vencimiento_tecnomecanico && now()->gt($v->fecha_vencimiento_tecnomecanico))->count(),
        ];
    }

    /**
     * Formatea un número como dinero en pesos colombianos.
     * 
     * @param float|int|string $amount - Cantidad a formatear
     * @return string Cantidad formateada (ej: $1.234.567)
     */
    private function money(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }
}
