<?php

namespace App\Filament\Pages;

use App\Concerns\HasUserContext;
use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ControlSemanal extends Page
{
    use HasUserContext;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Control Semanal';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Control Semanal';

    protected string $view = 'filament.pages.control-semanal';

    public string $selectedDate;

    public bool $isModalOpen = false;

    public ?int $selectedVehiculoId = null;

    public ?string $selectedFecha = null;

    public ?array $cachedVehiculo = null;

    public array $modalForm = [
        'trabajo' => true,
        'valor_generado' => 0,
        'gasto' => 0,
        'categoria_gasto' => 'otro',
        'observaciones' => '',
    ];

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function previousWeek(): void
    {
        $this->selectedDate = $this->weekStart()->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->selectedDate = $this->weekStart()->addWeek()->toDateString();
    }

    public function goToCurrentWeek(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function openRegistroModal(int $vehiculoId, string $fecha): void
    {
        $vehiculo = Vehiculo::query()->withTrashed()->with('persona')->findOrFail($vehiculoId);

        $bloqueado = $vehiculo->estado === 'mantenimiento'
            || ($vehiculo->estado === 'inactivo'
                && $vehiculo->fecha_inactivacion
                && Carbon::parse($fecha)->startOfDay()->gte($vehiculo->fecha_inactivacion->startOfDay()))
            || ($vehiculo->trashed()
                && $vehiculo->deleted_at
                && Carbon::parse($fecha)->startOfDay()->gte($vehiculo->deleted_at->startOfDay()))
            || ($vehiculo->fecha_eliminacion
                && $vehiculo->restored_at
                && Carbon::parse($fecha)->startOfDay()->gte($vehiculo->fecha_eliminacion->startOfDay())
                && Carbon::parse($fecha)->startOfDay()->lt($vehiculo->restored_at->startOfDay()));

        if ($bloqueado) {
            $razon = match (true) {
                $vehiculo->estado === 'mantenimiento' => 'en mantenimiento.',
                $vehiculo->estado === 'inactivo' => 'inactivo.',
                $vehiculo->trashed() => 'eliminado.',
                default => 'eliminado (restaurado).',
            };

            Notification::make()
                ->title('Registro bloqueado')
                ->body("No puedes editar registros de un vehículo {$razon}")
                ->danger()
                ->send();

            return;
        }

        $this->cachedVehiculo = [
            'id' => $vehiculo->id,
            'placa' => $vehiculo->placa,
            'cuota_diaria' => $vehiculo->cuota_diaria,
            'administracion' => $vehiculo->administracion ?? 0,
            'persona_nombre' => $vehiculo->persona?->nombre,
            'user_id' => $vehiculo->user_id,
        ];

        $registro = ControlDiario::query()
            ->where('vehiculo_id', $vehiculoId)
            ->whereDate('fecha', $fecha)
            ->first();

        $this->selectedVehiculoId = $vehiculoId;
        $this->selectedFecha = $fecha;
        $this->modalForm = [
            'trabajo' => $registro?->trabajo ?? true,
            'valor_generado' => $registro?->valor_generado ?? $vehiculo->cuota_diaria,
            'gasto' => $registro?->gasto ?? 0,
            'administracion' => $registro?->administracion ?? $vehiculo->administracion ?? 0,
            'categoria_gasto' => $registro?->categoria_gasto ?? 'otro',
            'observaciones' => $registro?->observaciones ?? '',
        ];
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->selectedVehiculoId = null;
        $this->selectedFecha = null;
        $this->cachedVehiculo = null;
        $this->modalForm = [
            'trabajo' => true,
            'valor_generado' => 0,
            'gasto' => 0,
            'administracion' => 0,
            'categoria_gasto' => 'otro',
            'observaciones' => '',
        ];
    }

    public function saveRegistro(): void
    {
        $rules = [
            'modalForm.trabajo' => ['required', 'boolean'],
            'modalForm.valor_generado' => ['required', 'numeric', 'min:0'],
            'modalForm.administracion' => ['nullable', 'numeric', 'min:0'],
            'modalForm.gasto' => ['nullable', 'numeric', 'min:0'],
            'modalForm.observaciones' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->modalForm['gasto'] > 0) {
            $rules['modalForm.categoria_gasto'] = ['required', 'in:daño,mantenimiento,multa,otro'];
        }

        $this->validate($rules);

        if (! $this->selectedVehiculoId || ! $this->selectedFecha) {
            return;
        }

        $vehiculo = Vehiculo::query()->withTrashed()->find($this->selectedVehiculoId);
        if ($vehiculo) {
            $bloqueado = $vehiculo->estado === 'mantenimiento'
                || ($vehiculo->estado === 'inactivo'
                    && $vehiculo->fecha_inactivacion
                    && Carbon::parse($this->selectedFecha)->startOfDay()->gte($vehiculo->fecha_inactivacion->startOfDay()))
                || ($vehiculo->trashed()
                    && $vehiculo->deleted_at
                    && Carbon::parse($this->selectedFecha)->startOfDay()->gte($vehiculo->deleted_at->startOfDay()))
                || ($vehiculo->fecha_eliminacion
                    && $vehiculo->restored_at
                    && Carbon::parse($this->selectedFecha)->startOfDay()->gte($vehiculo->fecha_eliminacion->startOfDay())
                    && Carbon::parse($this->selectedFecha)->startOfDay()->lt($vehiculo->restored_at->startOfDay()));
            if ($bloqueado) {
                $razon = match (true) {
                    $vehiculo->estado === 'mantenimiento' => 'en mantenimiento.',
                    $vehiculo->estado === 'inactivo' => 'inactivo.',
                    $vehiculo->trashed() => 'eliminado.',
                    default => 'eliminado (restaurado).',
                };

                Notification::make()
                    ->title('Registro bloqueado')
                    ->body("No puedes guardar cambios en un vehículo {$razon}")
                    ->danger()
                    ->send();
                $this->closeModal();

                return;
            }
        }

        $valorPorDefecto = (float) ($this->cachedVehiculo['cuota_diaria'] ?? 0);
        $adminPorDefecto = (float) ($this->cachedVehiculo['administracion'] ?? 0);

        $registro = ControlDiario::withoutGlobalScope('user')->firstOrNew([
            'vehiculo_id' => $this->selectedVehiculoId,
            'fecha' => $this->selectedFecha,
        ]);

        $trabajo = (bool) $this->modalForm['trabajo'];
        $valorGenerado = $trabajo ? (float) $this->modalForm['valor_generado'] : 0;
        $gasto = (float) ($this->modalForm['gasto'] ?? 0);
        $administracion = (float) ($this->modalForm['administracion'] ?? 0);
        $observaciones = trim((string) ($this->modalForm['observaciones'] ?? ''));

        $isDefault = $trabajo
            && abs($valorGenerado - $valorPorDefecto) < 0.01
            && abs($administracion - $adminPorDefecto) < 0.01
            && abs($gasto) < 0.01
            && $observaciones === '';

        if ($isDefault) {
            if ($registro->exists) {
                $registro->delete();
            }

            Notification::make()
                ->title('Registro restablecido al valor por defecto')
                ->success()
                ->send();

            $this->closeModal();

            return;
        }

        $registro->fill([
            'user_id' => $this->cachedVehiculo['user_id'] ?? auth()->id(),
            'trabajo' => $trabajo,
            'valor_generado' => $valorGenerado,
            'gasto' => $gasto,
            'administracion' => $administracion,
            'categoria_gasto' => ($gasto > 0 && isset($this->modalForm['categoria_gasto'])) ? $this->modalForm['categoria_gasto'] : null,
            'observaciones' => $observaciones ?: null,
        ]);
        $registro->save();

        Notification::make()
            ->title('Control semanal guardado')
            ->success()
            ->send();

        $this->closeModal();
    }

    public function getWeekDataset(): array
    {
        $weekStart = $this->weekStart();
        $weekEnd = $weekStart->copy()->addDays(6);

        $vehiculoQuery = Vehiculo::query()
            ->withTrashed()
            ->with(['persona', 'contratos'])
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('deleted_at')->where(function ($q) {
                    $q->where('estado', 'activo')
                        ->orWhere('estado', 'mantenimiento');
                })
                    ->orWhere(function ($q) use ($weekStart) {
                        $q->whereNull('deleted_at')
                            ->where('estado', 'inactivo')
                            ->whereNotNull('fecha_inactivacion')
                            ->where('fecha_inactivacion', '>', $weekStart);
                    })
                    ->orWhere(function ($q) use ($weekStart) {
                        $q->whereNotNull('deleted_at')
                            ->where('deleted_at', '>', $weekStart);
                    });
            })
            ->orderBy('placa');
        $this->applyUserScope($vehiculoQuery);
        $vehiculos = $vehiculoQuery->get();

        $fechas = collect(range(0, 6))
            ->map(fn (int $offset) => $weekStart->copy()->addDays($offset));

        $registros = $vehiculos->isEmpty()
            ? collect()
            : $this->applyUserScope(
                ControlDiario::query()
                    ->whereIn('vehiculo_id', $vehiculos->pluck('id'))
                    ->whereBetween('fecha', [$weekStart->toDateString(), $weekEnd->toDateString()])
            )->get()
                ->keyBy(fn (ControlDiario $registro) => $registro->fecha->toDateString().'-'.$registro->vehiculo_id);

        $rows = [];
        $vehicleTotals = [];
        $esperado = 0;
        $real = 0;
        $gastos = 0;
        $adminTotal = 0;
        $diasSinTrabajo = 0;
        $acumuladoSemana = 0;

        foreach ($vehiculos as $vehiculo) {
            $vehicleTotals[$vehiculo->id] = [
                'esperado' => 0,
                'real' => 0,
                'gastos' => 0,
                'neto' => 0,
            ];
        }

        foreach ($fechas as $fecha) {
            $row = [
                'fecha' => $fecha,
                'gastos' => 0,
                'total' => 0,
                'acumulado' => 0,
                'cells' => [],
            ];

            foreach ($vehiculos as $vehiculo) {
                $registro = $registros->get($fecha->toDateString().'-'.$vehiculo->id);

                if ($fecha->startOfDay()->lt($vehiculo->getEffectiveStartDate()) && ! $registro) {
                    $row['cells'][] = [
                        'vehiculo_id' => $vehiculo->id,
                        'fecha' => $fecha->toDateString(),
                        'ingreso' => 0,
                        'gasto' => 0,
                        'administracion' => 0,
                        'categoria_gasto' => null,
                        'trabajo' => false,
                        'has_changes' => false,
                        'observaciones' => null,
                        'not_applicable' => true,
                        'bloqueado' => false,
                        'estado' => $vehiculo->estado,
                    ];

                    continue;
                }

                $cellDisabled = $vehiculo->estado === 'mantenimiento'
                    || ($vehiculo->estado === 'inactivo'
                        && $vehiculo->fecha_inactivacion
                        && $fecha->startOfDay()->gte($vehiculo->fecha_inactivacion->startOfDay()))
                    || ($vehiculo->trashed()
                        && $vehiculo->deleted_at
                        && $fecha->startOfDay()->gte($vehiculo->deleted_at->startOfDay()))
                    || ($vehiculo->fecha_eliminacion
                        && $vehiculo->restored_at
                        && $fecha->startOfDay()->gte($vehiculo->fecha_eliminacion->startOfDay())
                        && $fecha->startOfDay()->lt($vehiculo->restored_at->startOfDay()));

                if ($cellDisabled && ! $registro) {
                    $row['cells'][] = [
                        'vehiculo_id' => $vehiculo->id,
                        'fecha' => $fecha->toDateString(),
                        'ingreso' => 0,
                        'gasto' => 0,
                        'administracion' => 0,
                        'categoria_gasto' => null,
                        'trabajo' => false,
                        'has_changes' => false,
                        'observaciones' => null,
                        'not_applicable' => true,
                        'bloqueado' => false,
                        'estado' => $vehiculo->estado,
                    ];

                    continue;
                }

                $valorBase = (float) $vehiculo->cuota_diaria;
                $adminBase = (float) $vehiculo->administracion;
                $trabajo = $registro?->trabajo ?? true;
                $ingreso = $trabajo
                    ? (float) ($registro?->valor_generado ?? $valorBase)
                    : 0;
                $gasto = (float) ($registro?->gasto ?? 0);
                $adminDia = $registro?->administracion ?? $adminBase;

                $row['cells'][] = [
                    'vehiculo_id' => $vehiculo->id,
                    'fecha' => $fecha->toDateString(),
                    'ingreso' => $ingreso,
                    'gasto' => $gasto,
                    'administracion' => $adminDia,
                    'categoria_gasto' => $registro?->categoria_gasto,
                    'trabajo' => $trabajo,
                    'has_changes' => $registro !== null,
                    'observaciones' => $registro?->observaciones,
                    'bloqueado' => $cellDisabled,
                    'estado' => $vehiculo->estado,
                ];

                $row['gastos'] += $gasto;
                $row['total'] += $ingreso - $gasto - $adminDia;
                $esperado += $valorBase;
                $real += $ingreso;
                $gastos += $gasto;
                $adminTotal += $adminDia;
                $vehicleTotals[$vehiculo->id]['esperado'] += $valorBase;
                $vehicleTotals[$vehiculo->id]['real'] += $ingreso;
                $vehicleTotals[$vehiculo->id]['gastos'] += $gasto;
                $vehicleTotals[$vehiculo->id]['neto'] += $ingreso - $gasto - $adminDia;

                if (! $trabajo) {
                    $diasSinTrabajo++;
                }
            }

            $rows[] = $row;
            $acumuladoSemana += $row['total'];
            $rows[array_key_last($rows)]['acumulado'] = $acumuladoSemana;
        }

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'vehiculos' => $vehiculos,
            'rows' => $rows,
            'vehicleTotals' => $vehicleTotals,
            'summary' => [
                'esperado' => $esperado,
                'real' => $real,
                'gastos' => $gastos,
                'administracion' => $adminTotal,
                'neto' => $real - $gastos - $adminTotal,
                'dias_sin_trabajo' => $diasSinTrabajo,
            ],
        ];
    }

    public function getWeekHistory(): array
    {
        $cacheKey = $this->userContextCacheKey().'_week_history_'.$this->weekStart()->toDateString();

        return Cache::remember($cacheKey, 60, function () {
            return $this->buildWeekHistory();
        });
    }

    private function buildWeekHistory(): array
    {
        $baseWeek = $this->weekStart();

        $oldestWeek = $baseWeek->copy()->subWeeks(11)->startOfWeek(Carbon::SUNDAY);
        $newestWeek = $baseWeek->copy()->addDays(6);

        $vehiculos = $this->applyUserScope(
            Vehiculo::query()->withTrashed()->with('contratos')
                ->where(function ($q) use ($oldestWeek) {
                    $q->whereNull('deleted_at')->where(function ($q) {
                        $q->where('estado', 'activo')
                            ->orWhere('estado', 'mantenimiento');
                    })
                        ->orWhere(function ($q) use ($oldestWeek) {
                            $q->whereNull('deleted_at')
                                ->where('estado', 'inactivo')
                                ->whereNotNull('fecha_inactivacion')
                                ->where('fecha_inactivacion', '>', $oldestWeek);
                        })
                        ->orWhere(function ($q) use ($oldestWeek) {
                            $q->whereNotNull('deleted_at')
                                ->where('deleted_at', '>', $oldestWeek);
                        });
                })
        )->get(['id', 'cuota_diaria', 'administracion', 'created_at', 'estado', 'fecha_inactivacion', 'deleted_at', 'fecha_eliminacion', 'restored_at']);

        $allRegistros = $vehiculos->isEmpty()
            ? collect()
            : $this->applyUserScope(
                ControlDiario::query()
                    ->whereIn('vehiculo_id', $vehiculos->pluck('id'))
                    ->whereBetween('fecha', [$oldestWeek->toDateString(), $newestWeek->toDateString()])
            )->get()
                ->groupBy(fn ($r) => $r->fecha->copy()->startOfWeek(Carbon::SUNDAY)->toDateString());

        $history = [];

        for ($index = 0; $index < 12; $index++) {
            $weekStart = $baseWeek->copy()->subWeeks($index)->startOfWeek(Carbon::SUNDAY);
            $weekEnd = $weekStart->copy()->addDays(6);
            $weekRegistros = $allRegistros->get($weekStart->toDateString(), collect());

            $item = $this->buildWeekHistoryFromData($weekStart, $weekEnd, $vehiculos, $weekRegistros);

            if (($item['novedades'] ?? 0) === 0) {
                continue;
            }

            $history[] = $item;
        }

        return $history;
    }

    public function money(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }

    public function dayLabel(Carbon $fecha): string
    {
        return match ($fecha->dayOfWeek) {
            Carbon::SUNDAY => 'Domingo',
            Carbon::MONDAY => 'Lunes',
            Carbon::TUESDAY => 'Martes',
            Carbon::WEDNESDAY => 'Miércoles',
            Carbon::THURSDAY => 'Jueves',
            Carbon::FRIDAY => 'Viernes',
            Carbon::SATURDAY => 'Sábado',
            default => $fecha->format('l'),
        };
    }

    public function selectedVehiculo(): ?array
    {
        return $this->cachedVehiculo;
    }

    private function weekStart(): Carbon
    {
        return Carbon::parse($this->selectedDate)->startOfWeek(Carbon::SUNDAY);
    }

    private function buildWeekHistoryFromData(
        Carbon $weekStart,
        Carbon $weekEnd,
        Collection $vehiculos,
        Collection $registros
    ): array {
        $esperado = 0;
        $real = 0;
        $gastos = 0;
        $adminTotal = 0;
        $diasSinTrabajo = 0;

        $registrosByKey = $registros->keyBy(fn ($r) => $r->vehiculo_id.'-'.$r->fecha->format('Y-m-d'));

        for ($offset = 0; $offset < 7; $offset++) {
            $key = $weekStart->copy()->addDays($offset)->format('Y-m-d');

            foreach ($vehiculos as $vehiculo) {
                $fecha = $weekStart->copy()->addDays($offset);

                if ($fecha->startOfDay()->lt($vehiculo->getEffectiveStartDate())) {
                    continue;
                }

                $cellDisabled = $vehiculo->estado === 'mantenimiento'
                    || ($vehiculo->estado === 'inactivo'
                        && $vehiculo->fecha_inactivacion
                        && $fecha->startOfDay()->gte($vehiculo->fecha_inactivacion->startOfDay()))
                    || ($vehiculo->trashed()
                        && $vehiculo->deleted_at
                        && $fecha->startOfDay()->gte($vehiculo->deleted_at->startOfDay()))
                    || ($vehiculo->fecha_eliminacion
                        && $vehiculo->restored_at
                        && $fecha->startOfDay()->gte($vehiculo->fecha_eliminacion->startOfDay())
                        && $fecha->startOfDay()->lt($vehiculo->restored_at->startOfDay()));

                if ($cellDisabled) {
                    continue;
                }

                $valorBase = (float) $vehiculo->cuota_diaria;
                $esperado += $valorBase;

                $registro = $registrosByKey->get($vehiculo->id.'-'.$key);
                $trabajo = $registro?->trabajo ?? true;
                $ingreso = $trabajo ? (float) ($registro?->valor_generado ?? $valorBase) : 0;
                $gasto = (float) ($registro?->gasto ?? 0);
                $adminDia = $registro?->administracion ?? (float) ($vehiculo->administracion ?? 0);

                $real += $ingreso;
                $gastos += $gasto;
                $adminTotal += $adminDia;

                if (! $trabajo) {
                    $diasSinTrabajo++;
                }
            }
        }

        return [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'esperado' => $esperado,
            'real' => $real,
            'gastos' => $gastos,
            'administracion' => $adminTotal,
            'neto' => $real - $gastos - $adminTotal,
            'dias_sin_trabajo' => $diasSinTrabajo,
            'novedades' => $registros->count(),
            'is_selected' => $weekStart->isSameDay($this->weekStart()),
        ];
    }
}
