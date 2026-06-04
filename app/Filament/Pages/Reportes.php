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

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Reportes';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    protected string $view = 'filament.pages.reportes';

    public string $periodo = 'este_mes';

    public ?string $fechaInicio = null;

    public ?string $fechaFin = null;

    public array $vehiculosSeleccionados = [];

    private ?Collection $cachedVehiculos = null;

    private ?Collection $cachedRegistros = null;

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
        return $this->cachedVehiculos ??= $this->applyUserScope(
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

        $registrosEnRango = $this->getRegistrosEnRango();
        $totalReal = 0;
        $totalGastos = 0;
        $totalAdmin = 0;
        $totalNoPercibido = 0;
        $diasNoTrabajados = 0;
        $totalDiferencia = 0;

        foreach ($vehiculos as $vehiculo) {
            if ($vehiculo->estado !== 'activo') {
                continue;
            }

            for ($d = 0; $d < $diasEnRango; $d++) {
                $fechaStr = $start->copy()->addDays($d)->toDateString();
                $registro = $registrosEnRango->get($fechaStr.'-'.$vehiculo->id);

                if ($registro) {
                    $realDia = $registro->trabajo ? (float) $registro->valor_generado : 0;
                    $totalReal += $realDia;
                    $totalGastos += (float) $registro->gasto;
                    $totalAdmin += (float) ($registro->administracion ?? $vehiculo->administracion ?? 0);

                    if (! $registro->trabajo) {
                        $totalNoPercibido += (float) $vehiculo->cuota_diaria;
                        $diasNoTrabajados++;
                    } elseif ((float) $registro->valor_generado != (float) $vehiculo->cuota_diaria) {
                        $totalDiferencia += $realDia - (float) $vehiculo->cuota_diaria;
                    }
                } else {
                    $totalReal += (float) $vehiculo->cuota_diaria;
                    $totalAdmin += (float) ($vehiculo->administracion ?? 0);
                }
            }
        }

        return [
            'esperado' => $totalEsperado,
            'real' => $totalReal,
            'gastos' => $totalGastos,
            'administracion' => $totalAdmin,
            'no_percibido' => $totalNoPercibido,
            'diferencia' => $totalDiferencia,
            'dias_no_trabajados' => $diasNoTrabajados,
            'neto' => $totalReal - $totalGastos - $totalAdmin,
            'dias' => $diasEnRango,
            'total_registros_modificados' => $registrosEnRango->count(),
        ];
    }

    public function getGastosPorCategoria(): array
    {
        $vehiculosActivosIds = $this->getVehiculosDisponibles()
            ->where('estado', 'activo')
            ->pluck('id');

        $registros = $this->getBaseQuery()
            ->where('gasto', '>', 0)
            ->whereIn('vehiculo_id', $vehiculosActivosIds)
            ->selectRaw('categoria_gasto, SUM(gasto) as total')
            ->groupBy('categoria_gasto')
            ->pluck('total', 'categoria_gasto');

        $gastos = [
            'daño' => (float) ($registros['daño'] ?? 0),
            'mantenimiento' => (float) ($registros['mantenimiento'] ?? 0),
            'multa' => (float) ($registros['multa'] ?? 0),
            'otro' => (float) ($registros['otro'] ?? 0),
        ];

        $dbTotal = $registros->sum();
        $gastos['otro'] += $dbTotal - array_sum($gastos);

        return [
            'categorias' => $gastos,
            'total' => $dbTotal,
        ];
    }

    public function getDetallePorVehiculo(): array
    {
        [$start, $end] = $this->getDateRange();
        $diasEnRango = max((int) $start->diffInDays($end) + 1, 1);

        $vehiculos = $this->getVehiculosDisponibles();
        $registrosEnRango = $this->getRegistrosEnRango();

        $detalle = [];

        foreach ($vehiculos as $vehiculo) {
            $real = 0;
            $gastos = 0;
            $admin = 0;
            $diasModificados = 0;
            $esperado = $vehiculo->estado === 'activo'
                ? (float) $vehiculo->cuota_diaria * $diasEnRango
                : 0;

            if ($vehiculo->estado === 'activo') {
                for ($d = 0; $d < $diasEnRango; $d++) {
                    $fechaStr = $start->copy()->addDays($d)->toDateString();
                    $registro = $registrosEnRango->get($fechaStr.'-'.$vehiculo->id);

                    if ($registro) {
                        $real += $registro->trabajo ? (float) $registro->valor_generado : 0;
                        $gastos += (float) $registro->gasto;
                        $admin += (float) ($registro->administracion ?? $vehiculo->administracion ?? 0);
                        $diasModificados++;
                    } else {
                        $real += (float) $vehiculo->cuota_diaria;
                        $admin += (float) ($vehiculo->administracion ?? 0);
                    }
                }
            }

            $detalle[] = [
                'placa' => $vehiculo->placa,
                'conductor' => $vehiculo->persona?->nombre ?? 'Sin conductor',
                'estado' => $vehiculo->estado,
                'esperado' => $esperado,
                'real' => $real,
                'gastos' => $gastos,
                'administracion' => $admin,
                'neto' => $real - $gastos - $admin,
                'dias_modificados' => $diasModificados,
                'cuota_diaria' => (float) $vehiculo->cuota_diaria,
            ];
        }

        return $detalle;
    }

    public function getDetalleDiario(): array
    {
        [$start, $end] = $this->getDateRange();

        $vehiculos = $this->getVehiculosDisponibles()->where('estado', 'activo');
        $registrosEnRango = $this->getRegistrosEnRango();

        if ($vehiculos->isEmpty()) {
            return [];
        }

        $dias = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $fechaStr = $current->toDateString();

            $real = 0;
            $gastos = 0;
            $admin = 0;
            $registros = 0;

            foreach ($vehiculos as $vehiculo) {
                $registro = $registrosEnRango->get($fechaStr.'-'.$vehiculo->id);

                if ($registro) {
                    $real += $registro->trabajo ? (float) $registro->valor_generado : 0;
                    $gastos += (float) $registro->gasto;
                    $admin += (float) ($registro->administracion ?? $vehiculo->administracion ?? 0);
                    $registros++;
                } else {
                    $real += (float) $vehiculo->cuota_diaria;
                    $admin += (float) ($vehiculo->administracion ?? 0);
                }
            }

            $dias[] = [
                'fecha' => $current->copy(),
                'real' => $real,
                'gastos' => $gastos,
                'administracion' => $admin,
                'neto' => $real - $gastos - $admin,
                'registros' => $registros,
            ];

            $current->addDay();
        }

        return $dias;
    }

    public function getAjustes(): array
    {
        $vehiculos = $this->getVehiculosDisponibles()->keyBy('id');
        $registros = $this->getRegistrosEnRango();

        $ajustes = [];

        foreach ($registros as $registro) {
            $vehiculo = $vehiculos->get($registro->vehiculo_id);
            if (! $vehiculo || $vehiculo->estado !== 'activo') {
                continue;
            }

            $esperado = (float) $vehiculo->cuota_diaria;
            $real = $registro->trabajo ? (float) $registro->valor_generado : 0;
            $diferencia = $real - $esperado;

            if ($diferencia === 0.0 && $registro->trabajo) {
                continue;
            }

            $ajustes[] = [
                'placa' => $vehiculo->placa,
                'conductor' => $vehiculo->persona?->nombre ?? 'Sin conductor',
                'fecha' => $registro->fecha,
                'esperado' => $esperado,
                'real' => $real,
                'diferencia' => $diferencia,
                'trabajo' => $registro->trabajo,
            ];
        }

        usort($ajustes, fn ($a, $b) => $a['fecha']->timestamp <=> $b['fecha']->timestamp
            ?: strcmp($a['placa'], $b['placa']));

        return $ajustes;
    }

    private function getRegistrosEnRango(): Collection
    {
        return $this->cachedRegistros ??= $this->getBaseQuery()
            ->get()
            ->keyBy(fn (ControlDiario $r) => $r->fecha->toDateString().'-'.$r->vehiculo_id);
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
