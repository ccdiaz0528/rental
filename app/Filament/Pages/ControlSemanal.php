<?php

namespace App\Filament\Pages;

use App\Models\ControlDiario;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ControlSemanal extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Control Semanal';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Control Semanal';

    protected string $view = 'filament.pages.control-semanal';

    public string $selectedDate;

    public bool $isModalOpen = false;

    public ?int $selectedVehiculoId = null;

    public ?string $selectedFecha = null;

    public array $modalForm = [
        'trabajo' => true,
        'valor_generado' => 0,
        'gasto' => 0,
        'observaciones' => '',
    ];

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
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
        $vehiculo = Vehiculo::query()->with('persona')->findOrFail($vehiculoId);

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
            'observaciones' => $registro?->observaciones ?? '',
        ];
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->selectedVehiculoId = null;
        $this->selectedFecha = null;
        $this->modalForm = [
            'trabajo' => true,
            'valor_generado' => 0,
            'gasto' => 0,
            'observaciones' => '',
        ];
    }

    public function saveRegistro(): void
    {
        $this->validate([
            'modalForm.trabajo' => ['required', 'boolean'],
            'modalForm.valor_generado' => ['required', 'numeric', 'min:0'],
            'modalForm.gasto' => ['nullable', 'numeric', 'min:0'],
            'modalForm.observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $this->selectedVehiculoId || ! $this->selectedFecha) {
            return;
        }

        $vehiculo = Vehiculo::query()->findOrFail($this->selectedVehiculoId);
        $registro = ControlDiario::query()->firstOrNew([
            'vehiculo_id' => $this->selectedVehiculoId,
            'fecha' => $this->selectedFecha,
        ]);

        $trabajo = (bool) $this->modalForm['trabajo'];
        $valorGenerado = $trabajo ? (float) $this->modalForm['valor_generado'] : 0;
        $gasto = (float) ($this->modalForm['gasto'] ?? 0);
        $observaciones = trim((string) ($this->modalForm['observaciones'] ?? ''));
        $valorPorDefecto = (float) $vehiculo->cuota_diaria;

        $isDefault = $trabajo
            && abs($valorGenerado - $valorPorDefecto) < 0.01
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
            'trabajo' => $trabajo,
            'valor_generado' => $valorGenerado,
            'gasto' => $gasto,
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
        $vehiculos = Vehiculo::query()
            ->with('persona')
            ->where('estado', 'activo')
            ->orderBy('placa')
            ->get();

        $fechas = collect(range(0, 6))
            ->map(fn (int $offset) => $weekStart->copy()->addDays($offset));

        $registros = $vehiculos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculos->pluck('id'))
                ->whereBetween('fecha', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get()
                ->keyBy(fn (ControlDiario $registro) => $registro->fecha->toDateString().'-'.$registro->vehiculo_id);

        $rows = [];
        $vehicleTotals = [];
        $esperado = 0;
        $real = 0;
        $gastos = 0;
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
                $valorBase = (float) $vehiculo->cuota_diaria;
                $trabajo = $registro?->trabajo ?? true;
                $ingreso = $trabajo
                    ? (float) ($registro?->valor_generado ?? $valorBase)
                    : 0;
                $gasto = (float) ($registro?->gasto ?? 0);

                $row['cells'][] = [
                    'vehiculo_id' => $vehiculo->id,
                    'fecha' => $fecha->toDateString(),
                    'ingreso' => $ingreso,
                    'gasto' => $gasto,
                    'trabajo' => $trabajo,
                    'has_changes' => $registro !== null,
                    'observaciones' => $registro?->observaciones,
                ];

                $row['gastos'] += $gasto;
                $row['total'] += $ingreso - $gasto;
                $esperado += $valorBase;
                $real += $ingreso;
                $gastos += $gasto;
                $vehicleTotals[$vehiculo->id]['esperado'] += $valorBase;
                $vehicleTotals[$vehiculo->id]['real'] += $ingreso;
                $vehicleTotals[$vehiculo->id]['gastos'] += $gasto;
                $vehicleTotals[$vehiculo->id]['neto'] += $ingreso - $gasto;

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
                'neto' => $real - $gastos,
                'dias_sin_trabajo' => $diasSinTrabajo,
            ],
        ];
    }

    public function getWeekHistory(): array
    {
        $history = [];
        $baseWeek = $this->weekStart();

        for ($index = 0; $index < 12; $index++) {
            $weekStart = $baseWeek->copy()->subWeeks($index);
            $item = $this->buildWeekHistoryItem($weekStart);

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

    public function selectedVehiculo(): ?Vehiculo
    {
        if (! $this->selectedVehiculoId) {
            return null;
        }

        return Vehiculo::query()->with('persona')->find($this->selectedVehiculoId);
    }

    private function weekStart(): Carbon
    {
        return Carbon::parse($this->selectedDate)->startOfWeek(Carbon::SUNDAY);
    }

    private function buildWeekHistoryItem(Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->addDays(6);
        $vehiculos = Vehiculo::query()
            ->where('estado', 'activo')
            ->get(['id', 'cuota_diaria']);

        $registros = $vehiculos->isEmpty()
            ? collect()
            : ControlDiario::query()
                ->whereIn('vehiculo_id', $vehiculos->pluck('id'))
                ->whereBetween('fecha', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get();

        $esperado = 0;
        $real = 0;
        $gastos = 0;
        $diasSinTrabajo = 0;

        foreach (range(0, 6) as $offset) {
            $fecha = $weekStart->copy()->addDays($offset);

            foreach ($vehiculos as $vehiculo) {
                $registro = $registros->first(fn (ControlDiario $item) => $item->vehiculo_id === $vehiculo->id && $item->fecha->isSameDay($fecha));
                $valorBase = (float) $vehiculo->cuota_diaria;
                $trabajo = $registro?->trabajo ?? true;
                $ingreso = $trabajo ? (float) ($registro?->valor_generado ?? $valorBase) : 0;
                $gasto = (float) ($registro?->gasto ?? 0);

                $esperado += $valorBase;
                $real += $ingreso;
                $gastos += $gasto;

                if (! $trabajo) {
                    $diasSinTrabajo++;
                }
            }
        }

        return [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'esperado' => $esperado,
            'real' => $real,
            'gastos' => $gastos,
            'neto' => $real - $gastos,
            'dias_sin_trabajo' => $diasSinTrabajo,
            'novedades' => $registros->count(),
            'is_selected' => $weekStart->isSameDay($this->weekStart()),
        ];
    }
}
