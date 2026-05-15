<?php

namespace App\Filament\Pages;

use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Reportes extends Page
{
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

    public function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
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

    /**
     * @return array{Carbon, Carbon}
     */
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
        $query = Vehiculo::query()->orderBy('placa');

        if (! $this->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query->get();
    }

    public function getResumen(): array
    {
        $registros = $this->getBaseQuery()->get();
        $vehiculos = $this->getVehiculosDisponibles();
        [$start, $end] = $this->getDateRange();

        $diasEnRango = max((int) $start->diffInDays($end) + 1, 1);

        $totalReal = (float) $registros->sum('valor_generado');
        $totalGastos = (float) $registros->sum('gasto');
        $totalNeto = $totalReal - $totalGastos;

        $totalEsperado = 0;
        foreach ($vehiculos as $vehiculo) {
            if ($vehiculo->estado === 'activo') {
                $totalEsperado += (float) $vehiculo->cuota_diaria * $diasEnRango;
            }
        }

        return [
            'esperado' => $totalEsperado,
            'real' => $totalReal,
            'gastos' => $totalGastos,
            'neto' => $totalNeto,
            'dias' => $diasEnRango,
            'total_registros_modificados' => $registros->count(),
        ];
    }

    public function getGastosPorCategoria(): array
    {
        $registros = $this->getBaseQuery()
            ->where('gasto', '>', 0)
            ->get();

        $gastos = ['daño' => 0.0, 'mantenimiento' => 0.0, 'multa' => 0.0, 'otro' => 0.0];

        foreach ($registros as $r) {
            $cat = $r->categoria_gasto ?: 'otro';
            $gastos[$cat] += (float) ($r->gasto ?? 0);
        }

        $total = array_sum($gastos);

        return [
            'categorias' => $gastos,
            'total' => $total,
        ];
    }

    public function getDetallePorVehiculo(): array
    {
        [$start, $end] = $this->getDateRange();
        $vehiculos = $this->getVehiculosDisponibles();

        $registrosPorVehiculo = $this->getBaseQuery()
            ->get()
            ->groupBy('vehiculo_id');

        $detalle = [];

        foreach ($vehiculos as $vehiculo) {
            $regs = $registrosPorVehiculo->get($vehiculo->id, collect());

            $real = (float) $regs->sum('valor_generado');
            $gastos = (float) $regs->sum('gasto');
            $neto = $real - $gastos;
            $diasModificados = $regs->count();

            $diasEnRango = max((int) $start->diffInDays($end) + 1, 1);
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
                'neto' => $neto,
                'dias_modificados' => $diasModificados,
                'cuota_diaria' => (float) $vehiculo->cuota_diaria,
            ];
        }

        return $detalle;
    }

    public function getDetalleDiario(): array
    {
        [$start, $end] = $this->getDateRange();

        $registros = $this->getBaseQuery()
            ->get()
            ->groupBy(fn ($r) => $r->fecha->toDateString());

        $dias = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $fechaStr = $current->toDateString();
            $regs = $registros->get($fechaStr, collect());

            $real = (float) $regs->sum('valor_generado');
            $gastos = (float) $regs->sum('gasto');
            $neto = $real - $gastos;

            $dias[] = [
                'fecha' => $current->copy(),
                'real' => $real,
                'gastos' => $gastos,
                'neto' => $neto,
                'registros' => $regs->count(),
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

        $query = ControlDiario::query()
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()]);

        if (! empty($this->vehiculosSeleccionados)) {
            $query->whereIn('vehiculo_id', $this->vehiculosSeleccionados);
        }

        if (! $this->isAdmin()) {
            $query->where('control_diarios.user_id', auth()->id());
        }

        return $query;
    }
}
