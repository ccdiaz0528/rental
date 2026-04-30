<x-filament-panels::page>
    @php($dataset = $this->getWeekDataset())
    @php($history = $this->getWeekHistory())
    @php($headerPalettes = [
        ['bg' => 'bg-cyan-500', 'soft' => 'bg-cyan-50 dark:bg-cyan-500/10', 'text' => 'text-cyan-700 dark:text-cyan-300'],
        ['bg' => 'bg-emerald-500', 'soft' => 'bg-emerald-50 dark:bg-emerald-500/10', 'text' => 'text-emerald-700 dark:text-emerald-300'],
        ['bg' => 'bg-violet-500', 'soft' => 'bg-violet-50 dark:bg-violet-500/10', 'text' => 'text-violet-700 dark:text-violet-300'],
        ['bg' => 'bg-amber-500', 'soft' => 'bg-amber-50 dark:bg-amber-500/10', 'text' => 'text-amber-700 dark:text-amber-300'],
        ['bg' => 'bg-rose-500', 'soft' => 'bg-rose-50 dark:bg-rose-500/10', 'text' => 'text-rose-700 dark:text-rose-300'],
        ['bg' => 'bg-sky-600', 'soft' => 'bg-sky-50 dark:bg-sky-500/10', 'text' => 'text-sky-700 dark:text-sky-300'],
    ])

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.22),_transparent_28%),linear-gradient(135deg,#0f172a_0%,#111827_55%,#1d4ed8_100%)] text-white shadow-[0_30px_80px_rgba(15,23,42,0.28)] dark:border-white/10">
            <div class="grid gap-8 px-7 py-7 xl:grid-cols-[minmax(0,1fr)_26rem] xl:items-stretch">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100 backdrop-blur-sm">
                        Control semanal
                    </div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight lg:text-4xl">
                        {{ $dataset['weekStart']->format('d/m/Y') }} al {{ $dataset['weekEnd']->format('d/m/Y') }}
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200/90">
                        Revisa lo que debía producir la flota, descuenta gastos por día y detecta rápidamente qué vehículo no trabajó.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3 text-xs font-medium text-slate-200">
                        <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">Semana domingo a sábado</span>
                        <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">Edición por celda</span>
                        <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5">Control por vehículo</span>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-[24px] border border-white/10 bg-white/10 p-5 backdrop-blur-md xl:min-h-[140px]">
                        <p class="text-[11px] uppercase tracking-[0.24em] text-slate-300">Neto semanal</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $this->money($dataset['summary']['neto']) }}</p>
                        <div class="mt-3 text-xs text-slate-300">Ingreso ajustado menos gastos</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 xl:min-h-[140px]">
                        <div class="rounded-[20px] border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-300">Gastos</p>
                            <p class="mt-2 text-xl font-semibold">{{ $this->money($dataset['summary']['gastos']) }}</p>
                        </div>
                        <div class="rounded-[20px] border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-300">Sin trabajo</p>
                            <p class="mt-2 text-xl font-semibold">{{ $dataset['summary']['dias_sin_trabajo'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4 dark:border-white/10 dark:bg-white/5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Cuadro semanal</h3>
                        <p class="text-sm text-slate-500">Haz clic en una celda para ajustar ingreso, gasto u observación de ese vehículo en ese día.</p>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-[220px_auto] lg:items-end">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Ir a una fecha</span>
                            <input
                                type="date"
                                wire:model.live="selectedDate"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-gray-950"
                            >
                        </label>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <button wire:click="previousWeek" type="button" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">
                                Semana anterior
                            </button>
                            <button wire:click="goToCurrentWeek" type="button" class="rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 dark:bg-primary-600 dark:hover:bg-primary-500">
                                Semana actual
                            </button>
                            <button wire:click="nextWeek" type="button" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">
                                Semana siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100/90 dark:bg-white/5">
                        <tr>
                            <th class="sticky left-0 z-20 min-w-44 border-b border-r border-slate-200 bg-slate-100 px-4 py-4 text-left font-semibold text-slate-900 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                Día
                            </th>
                            @forelse ($dataset['vehiculos'] as $vehiculo)
                                @php($palette = $headerPalettes[$loop->index % count($headerPalettes)])
                                <th class="min-w-52 border-b border-r border-slate-200 px-3 py-3 text-center dark:border-white/10">
                                    <div class="rounded-[20px] {{ $palette['soft'] }} px-3 py-3">
                                        <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full {{ $palette['bg'] }} text-xs font-bold text-white">
                                            {{ strtoupper(substr($vehiculo->placa, 0, 2)) }}
                                        </div>
                                        <div class="mt-3 text-sm font-semibold text-slate-950 dark:text-white">{{ $vehiculo->placa }}</div>
                                        <div class="mt-1 text-xs font-normal text-slate-500">
                                            {{ $vehiculo->persona?->nombre ?? 'Sin conductor' }}
                                        </div>
                                        <div class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $palette['soft'] }} {{ $palette['text'] }} ring-1 ring-inset ring-black/5 dark:ring-white/10">
                                            Cuota {{ $this->money($vehiculo->cuota_diaria) }}
                                        </div>
                                    </div>
                                </th>
                            @empty
                                <th class="border-b border-r border-slate-200 px-4 py-3 text-left dark:border-white/10">No hay vehículos activos</th>
                            @endforelse
                            <th class="min-w-36 border-b border-r border-slate-200 px-4 py-4 text-center font-semibold text-danger-700 dark:border-white/10 dark:text-danger-300">
                                Gastos
                            </th>
                            <th class="min-w-36 border-b border-r border-slate-200 px-4 py-4 text-center font-semibold text-success-700 dark:border-white/10 dark:text-success-300">
                                Total día
                            </th>
                            <th class="min-w-40 border-b border-slate-200 px-4 py-4 text-center font-semibold text-slate-900 dark:border-white/10 dark:text-white">
                                Acumulado semana
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataset['rows'] as $row)
                            <tr class="border-b border-slate-200 last:border-b-0 hover:bg-slate-50/80 dark:border-white/10 dark:hover:bg-white/[0.03]">
                                <td class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-4 align-top dark:border-white/10 dark:bg-gray-900">
                                    <div class="font-medium text-slate-950 dark:text-white">{{ $this->dayLabel($row['fecha']) }}</div>
                                    <div class="text-xs text-slate-500">{{ $row['fecha']->format('d/m/Y') }}</div>
                                </td>

                                @foreach ($row['cells'] as $cell)
                                    <td class="border-r border-slate-200 px-2 py-2 text-center align-top dark:border-white/10">
                                        <button
                                            type="button"
                                            wire:click="openRegistroModal({{ $cell['vehiculo_id'] }}, '{{ $cell['fecha'] }}')"
                                            class="w-full rounded-[20px] border px-3 py-4 text-sm shadow-sm transition {{ ! $cell['trabajo'] ? 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300' : ($cell['gasto'] > 0 ? 'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300' : ($cell['has_changes'] ? 'border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-300' : 'border-slate-200 bg-white text-slate-800 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100 dark:hover:bg-white/10')) }}"
                                        >
                                            <div class="text-base font-semibold">{{ $this->money($cell['ingreso']) }}</div>
                                            @if ($cell['gasto'] > 0)
                                                <div class="mt-1 text-xs font-medium">Gasto: {{ $this->money($cell['gasto']) }}</div>
                                            @endif
                                            @if (! $cell['trabajo'])
                                                <div class="mt-1 text-xs font-medium">No trabajó</div>
                                            @endif
                                            @if ($cell['observaciones'])
                                                <div class="mt-2 line-clamp-2 text-[11px] opacity-80">{{ $cell['observaciones'] }}</div>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach

                                <td class="border-r border-slate-200 px-4 py-4 text-right font-semibold text-danger-600 dark:border-white/10">
                                    {{ $this->money($row['gastos']) }}
                                </td>
                                <td class="border-r border-slate-200 px-4 py-4 text-right font-semibold {{ $row['total'] >= 0 ? 'text-success-600' : 'text-danger-600' }} dark:border-white/10">
                                    {{ $this->money($row['total']) }}
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-900 dark:text-white">
                                    {{ $this->money($row['acumulado']) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-slate-100/70 dark:bg-white/5">
                            <td class="sticky left-0 z-10 border-r border-t border-slate-200 bg-slate-100 px-4 py-4 align-top font-semibold text-slate-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                Total semanal
                                <div class="mt-1 text-xs font-normal text-slate-500">Resumen por vehículo</div>
                            </td>

                            @foreach ($dataset['vehiculos'] as $vehiculo)
                                @php($totalVehiculo = $dataset['vehicleTotals'][$vehiculo->id] ?? ['real' => 0, 'gastos' => 0, 'neto' => 0])
                                <td class="border-r border-t border-slate-200 px-4 py-4 text-center dark:border-white/10">
                                    <div class="text-base font-semibold {{ $totalVehiculo['neto'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        {{ $this->money($totalVehiculo['neto']) }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Ing: {{ $this->money($totalVehiculo['real']) }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Gas: {{ $this->money($totalVehiculo['gastos']) }}
                                    </div>
                                </td>
                            @endforeach

                            <td class="border-r border-t border-slate-200 px-4 py-4 text-right font-semibold text-danger-600 dark:border-white/10">
                                {{ $this->money($dataset['summary']['gastos']) }}
                            </td>
                            <td class="border-r border-t border-slate-200 px-4 py-4 text-right font-semibold {{ $dataset['summary']['neto'] >= 0 ? 'text-success-600' : 'text-danger-600' }} dark:border-white/10">
                                {{ $this->money($dataset['summary']['neto']) }}
                            </td>
                            <td class="border-t border-slate-200 px-4 py-4 text-right font-semibold text-slate-950 dark:border-white/10 dark:text-white">
                                {{ $this->money($dataset['summary']['neto']) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

            <div class="grid gap-8 2xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Esperado semanal</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $this->money($dataset['summary']['esperado']) }}</p>
                        <p class="mt-2 text-xs text-slate-500">Suma de cuotas sin ajustes</p>
                    </article>
                    <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Ingreso ajustado</p>
                        <p class="mt-2 text-2xl font-semibold text-primary-600">{{ $this->money($dataset['summary']['real']) }}</p>
                        <p class="mt-2 text-xs text-slate-500">Incluye días sin trabajar y cambios manuales</p>
                    </article>
                    <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Gastos cargados</p>
                        <p class="mt-2 text-2xl font-semibold text-danger-600">{{ $this->money($dataset['summary']['gastos']) }}</p>
                        <p class="mt-2 text-xs text-slate-500">Descuentos semanales registrados</p>
                    </article>
                    <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Vehículos activos</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $dataset['vehiculos']->count() }}</p>
                        <p class="mt-2 text-xs text-slate-500">Columnas visibles esta semana</p>
                    </article>
                </section>
            </div>

            <aside class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 2xl:sticky 2xl:top-6 2xl:self-start">
                <div>
                    <p class="text-sm font-medium text-slate-500">Historial</p>
                    <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Últimas 12 semanas</h3>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($history as $week)
                        <button
                            type="button"
                            wire:click="$set('selectedDate', '{{ $week['week_start']->toDateString() }}')"
                            class="w-full rounded-[22px] border px-4 py-4 text-left transition {{ $week['is_selected'] ? 'border-slate-900 bg-slate-950 text-white dark:border-primary-400 dark:bg-primary-500/10' : 'border-slate-200 hover:border-slate-400 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5' }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold {{ $week['is_selected'] ? 'text-white' : 'text-slate-950 dark:text-white' }}">
                                        {{ $week['week_start']->format('d/m') }} - {{ $week['week_end']->format('d/m') }}
                                    </div>
                                    <div class="mt-1 text-xs {{ $week['is_selected'] ? 'text-slate-300' : 'text-slate-500' }}">
                                        {{ $week['novedades'] }} ajustes · {{ $week['dias_sin_trabajo'] }} días no trabajados
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs {{ $week['is_selected'] ? 'text-slate-300' : 'text-slate-500' }}">Neto</div>
                                    <div class="text-sm font-semibold {{ $week['neto'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        {{ $this->money($week['neto']) }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div class="rounded-2xl px-3 py-2 {{ $week['is_selected'] ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200' }}">
                                    Esp: {{ $this->money($week['esperado']) }}
                                </div>
                                <div class="rounded-2xl px-3 py-2 {{ $week['is_selected'] ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200' }}">
                                    Ing: {{ $this->money($week['real']) }}
                                </div>
                                <div class="rounded-2xl px-3 py-2 {{ $week['is_selected'] ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200' }}">
                                    Gas: {{ $this->money($week['gastos']) }}
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-[22px] border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                            Aun no hay semanas anteriores con registros guardados.
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>

    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4">
            <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold">Ajustar registro semanal</h3>
                            <p class="text-sm text-gray-500">
                                {{ $this->selectedVehiculo()?->placa }}
                                @if ($this->selectedVehiculo()?->persona?->nombre)
                                    · {{ $this->selectedVehiculo()?->persona?->nombre }}
                                @endif
                                · {{ $selectedFecha ? \Carbon\Carbon::parse($selectedFecha)->format('d/m/Y') : '' }}
                            </p>
                        </div>
                        <button type="button" wire:click="closeModal" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10">
                            X
                        </button>
                    </div>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 dark:border-white/10">
                        <input type="checkbox" wire:model.live="modalForm.trabajo" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <div class="font-medium">El vehículo trabajó este día</div>
                            <div class="text-sm text-gray-500">Si lo desmarcas, el ingreso del día queda en cero.</div>
                        </div>
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Valor generado</span>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="modalForm.valor_generado"
                                @disabled(! $modalForm['trabajo'])
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-white/10 dark:bg-gray-950 dark:disabled:bg-white/5"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Gasto del día</span>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="modalForm.gasto"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-gray-950"
                            >
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Observaciones</span>
                        <textarea
                            wire:model="modalForm.observaciones"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-gray-950"
                        ></textarea>
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-white/10">
                    <button type="button" wire:click="closeModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">
                        Cancelar
                    </button>
                    <button type="button" wire:click="saveRegistro" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
