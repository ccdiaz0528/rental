<?php

namespace App\Filament\Pages;

use App\Concerns\HasUserContext;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Reportes extends Page
{
    use HasUserContext;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Reportes';

    protected string $view = 'filament.pages.reportes';

    public string $periodo = 'este_mes';

    public ?string $fechaInicio = null;

    public ?string $fechaFin = null;

    public array $vehiculosSeleccionados = [];

    public function mount(): void
    {
        $range = $this->getDateRange();
        $this->fechaInicio = $range[0]->toDateString();
        $this->fechaFin = $range[1]->toDateString();
    }

    public function updatedPeriodo(string $value): void
    {
        $range = $this->getDateRange();
        $this->fechaInicio = $range[0]->toDateString();
        $this->fechaFin = $range[1]->toDateString();
    }

    public function getPeriodoLabel(): string
    {
        return match ($this->periodo) {
            'hoy' => 'Hoy',
            'ayer' => 'Ayer',
            'esta_semana' => 'Esta semana',
            'semana_pasada' => 'Semana pasada',
            'este_mes' => 'Este mes',
            'mes_pasado' => 'Mes pasado',
            'este_trimestre' => 'Este trimestre',
            'este_semestre' => 'Este semestre',
            'este_anio' => 'Este año',
            'anio_pasado' => 'Año pasado',
            'personalizado' => 'Personalizado',
            default => 'Este mes',
        };
    }

    public function getDateRange(): array
    {
        $now = now();

        return match ($this->periodo) {
            'hoy' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'ayer' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'esta_semana' => [$now->copy()->startOfWeek(Carbon::SUNDAY), $now->copy()->endOfWeek(Carbon::SATURDAY)],
            'semana_pasada' => [$now->copy()->subWeek()->startOfWeek(Carbon::SUNDAY), $now->copy()->subWeek()->endOfWeek(Carbon::SATURDAY)],
            'este_mes' => [$now->copy()->startOfMonth(), $now->copy()],
            'mes_pasado' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'este_trimestre' => [$now->copy()->firstOfQuarter(), $now->copy()],
            'este_semestre' => [$this->semestreStart($now), $now->copy()],
            'este_anio' => [$now->copy()->startOfYear(), $now->copy()],
            'anio_pasado' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'personalizado' => [
                $this->fechaInicio ? Carbon::parse($this->fechaInicio)->startOfDay() : $now->copy()->startOfMonth(),
                $this->fechaFin ? Carbon::parse($this->fechaFin)->endOfDay() : $now->copy(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()],
        };
    }

    private function semestreStart(Carbon $date): Carbon
    {
        return $date->month <= 6
            ? $date->copy()->startOfYear()
            : $date->copy()->month(7)->startOfMonth();
    }

    public function getVehiculosDisponibles(): Collection
    {
        return $this->applyUserScope(
            Vehiculo::query()->with('persona:id,nombre')->orderBy('placa')
        )->get();
    }

    public function getResumen(): array
    {
        [$start, $end] = $this->getDateRange();
        $diasEnRango = max((int) $start->diffInDays($end) + 1, 1);

        $vehiculos = $this->getVehiculosDisponibles();
        $totalEsperado = 0;
        foreach ($vehiculos as $vehiculo) {
            if ($vehiculo->estado === 'activo') {
                $totalEsperado += (float) $vehiculo->cuota_diaria * $diasEnRango;
            }
        }

        $agregados = $this->getBaseQuery()
            ->selectRaw('COALESCE(SUM(valor_generado), 0) as total_real, COALESCE(SUM(gasto), 0) as total_gastos')
            ->first();

        $totalReal = (float) ($agregados?->total_real ?? 0);
        $totalGastos = (float) ($agregados?->total_gastos ?? 0);

        return [
            'esperado' => $totalEsperado,
            'real' => $totalReal,
            'gastos' => $totalGastos,
            'neto' => $totalReal - $totalGastos,
            'dias' => $diasEnRango,
            'total_registros_modificados' => $this->getBaseQuery()->count(),
        ];
    }

    public function getGastosPorCategoria(): array
    {
        $registros = $this->getBaseQuery()
            ->where('gasto', '>', 0)
            ->selectRaw('categoria_gasto, SUM(gasto) as total')
            ->groupBy('categoria_gasto')
            ->pluck('total', 'categoria_gasto');

        $gastos = [
            'daño' => (float) ($registros['daño'] ?? 0),
            'mantenimiento' => (float) ($registros['mantenimiento'] ?? 0),
            'multa' => (float) ($registros['multa'] ?? 0),
            'otro' => (float) ($registros['otro'] ?? 0),
        ];

        return [
            'categorias' => $gastos,
            'total' => array_sum($gastos),
        ];
    }

    public function getDetallePorVehiculo(): array
    {
        [$start, $end] = $this->getDateRange();
        $diasEnRango = max((int) $start->diffInDays($end) + 1, 1);

        $vehiculos = $this->getVehiculosDisponibles();

        $agregadosPorVehiculo = $this->getBaseQuery()
            ->selectRaw('vehiculo_id, COALESCE(SUM(valor_generado), 0) as total_real, COALESCE(SUM(gasto), 0) as total_gastos, COUNT(*) as total_registros')
            ->groupBy('vehiculo_id')
            ->get()
            ->keyBy('vehiculo_id');

        $detalle = [];

        foreach ($vehiculos as $vehiculo) {
            $agg = $agregadosPorVehiculo->get($vehiculo->id);

            $real = (float) ($agg?->total_real ?? 0);
            $gastos = (float) ($agg?->total_gastos ?? 0);
            $esperado = $vehiculo->estado === 'activo'
                ? (float) $vehiculo->cuota_diaria * $diasEnRango
                : 0;

            $detalle[] = [
                'placa' => $vehiculo->placa,
                'conductor' => $vehiculo->persona?->nombre ?? 'Sin conductor',
                'estado' => $vehiculo->estado,
                'esperado' => $esperado,
                'real' => $real,
                'gastos' => $gastos,
                'neto' => $real - $gastos,
                'dias_modificados' => (int) ($agg?->total_registros ?? 0),
                'cuota_diaria' => (float) $vehiculo->cuota_diaria,
            ];
        }

        return $detalle;
    }

    public function getDetalleDiario(): array
    {
        [$start, $end] = $this->getDateRange();

        $agregadosPorDia = $this->getBaseQuery()
            ->selectRaw('fecha, COALESCE(SUM(valor_generado), 0) as total_real, COALESCE(SUM(gasto), 0) as total_gastos, COUNT(*) as total_registros')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->keyBy(fn ($r) => $r->fecha->toDateString());

        $dias = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $fechaStr = $current->toDateString();
            $agg = $agregadosPorDia->get($fechaStr);

            $real = (float) ($agg?->total_real ?? 0);
            $gastos = (float) ($agg?->total_gastos ?? 0);

            $dias[] = [
                'fecha' => $current->copy(),
                'real' => $real,
                'gastos' => $gastos,
                'neto' => $real - $gastos,
                'registros' => (int) ($agg?->total_registros ?? 0),
            ];

            $current->addDay();
        }

        return $dias;
    }

    public function money(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }

    public function porcentaje(float $valor, float $total): string
    {
        if ($total <= 0) {
            return '0%';
        }

        return number_format(($valor / $total) * 100, 1).'%';
    }

    private function getBaseQuery(): Builder
    {
        [$start, $end] = $this->getDateRange();

        $query = $this->applyUserScope(
            ControlDiario::query()
                ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
        );

        if (! empty($this->vehiculosSeleccionados)) {
            $query->whereIn('vehiculo_id', $this->vehiculosSeleccionados);
        }

        return $query;
    }
}
