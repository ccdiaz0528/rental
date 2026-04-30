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
        $vehiculos = Vehiculo::query()
            ->where('estado', 'activo')
            ->get(['id', 'cuota_diaria', 'persona_id']);
        $registrosSemana = $vehiculos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculos->pluck('id'))
                ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                ->get();

        $hoyCalculado = $this->calcularDia($hoy, $vehiculos, $registrosSemana);
        $semanaCalculada = $this->calcularSemana($inicioSemana, $vehiculos, $registrosSemana);
        $vehiculosActivos = $vehiculos->count();
        $contratosActivos = Contrato::where('estado', 'activo')->count();
        $vehiculosConConductor = $vehiculos->whereNotNull('persona_id')->count();
        $novedadesSemana = $registrosSemana->count();

        return [
            Stat::make('Esperado hoy', $this->money($hoyCalculado['esperado']))
                ->description('Cuota base de vehículos activos')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info'),

            Stat::make('Neto hoy', $this->money($hoyCalculado['neto']))
                ->description('Ingreso del día menos gastos')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($hoyCalculado['neto'] >= 0 ? 'success' : 'danger'),

            Stat::make('Gastos hoy', $this->money($hoyCalculado['gastos']))
                ->description('Ajustes cargados para hoy')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger'),

            Stat::make('Esperado semanal', $this->money($semanaCalculada['esperado']))
                ->description('Semana domingo a sábado')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Gastos semanales', $this->money($semanaCalculada['gastos']))
                ->description('Gastos de la semana actual')
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('warning'),

            Stat::make('Neto semanal', $this->money($semanaCalculada['neto']))
                ->description('Ingreso ajustado menos gastos')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($semanaCalculada['neto'] >= 0 ? 'success' : 'danger'),

            Stat::make('Vehículos activos', $vehiculosActivos)
                ->description('En operación actualmente')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Contratos activos', $contratosActivos)
                ->description('Contratos vigentes')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),

            Stat::make('Vehículos con conductor', $vehiculosConConductor)
                ->description('Ya asignados a una persona')
                ->descriptionIcon('heroicon-o-user')
                ->color('success'),

            Stat::make('Novedades esta semana', $novedadesSemana)
                ->description('Celdas ajustadas manualmente')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color($novedadesSemana > 0 ? 'warning' : 'gray'),
        ];
    }

    private function calcularDia(Carbon $fecha, Collection $vehiculos, Collection $registrosSemana): array
    {
        $registros = $registrosSemana
            ->filter(fn (ControlDiario $registro) => $registro->fecha->isSameDay($fecha))
            ->keyBy('vehiculo_id');

        $esperado = 0;
        $real = 0;
        $gastos = 0;

        foreach ($vehiculos as $vehiculo) {
            $esperado += (float) $vehiculo->cuota_diaria;
            $registro = $registros->get($vehiculo->id);
            $real += $registro
                ? ($registro->trabajo ? (float) $registro->valor_generado : 0)
                : (float) $vehiculo->cuota_diaria;
            $gastos += (float) ($registro?->gasto ?? 0);
        }

        return [
            'esperado' => $esperado,
            'real' => $real,
            'gastos' => $gastos,
            'neto' => $real - $gastos,
        ];
    }

    private function calcularSemana(Carbon $inicioSemana, Collection $vehiculos, Collection $registrosSemana): array
    {
        $esperado = 0;
        $real = 0;
        $gastos = 0;

        foreach (range(0, 6) as $offset) {
            $dia = $inicioSemana->copy()->addDays($offset);
            $calculado = $this->calcularDia($dia, $vehiculos, $registrosSemana);
            $esperado += $calculado['esperado'];
            $real += $calculado['real'];
            $gastos += $calculado['gastos'];
        }

        return [
            'esperado' => $esperado,
            'real' => $real,
            'gastos' => $gastos,
            'neto' => $real - $gastos,
        ];
    }

    private function money(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }
}
