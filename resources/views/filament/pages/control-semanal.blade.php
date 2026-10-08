<x-filament-panels::page>
    @php($dataset = $this->getWeekDataset())
    @php($history = $this->getWeekHistory())

    <div class="space-y-6 tabular-nums w-full min-w-0">
        <section class="overflow-hidden rounded-[32px] border border-gray-200 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.10),_transparent_28%),linear-gradient(135deg,#ffffff_0%,#f8fafc_55%,#f1f5f9_100%)] text-slate-900 shadow-[0_8px_30px_rgba(0,0,0,0.06)] dark:border-white/10 dark:bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.22),_transparent_28%),linear-gradient(135deg,#0f172a_0%,#111827_55%,#1e293b_100%)] dark:text-white dark:shadow-[0_30px_80px_rgba(15,23,42,0.28)]">
            <div class="grid gap-8 px-7 py-7 xl:grid-cols-[minmax(0,1fr)_26rem] xl:items-stretch">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:border-white/15 dark:bg-white/10 dark:text-info-100 dark:backdrop-blur-sm">
                        Control semanal
                    </div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight lg:text-4xl">
                        {{ $dataset['weekStart']->format('d/m/Y') }} al {{ $dataset['weekEnd']->format('d/m/Y') }}
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-200/90">
                        Revisa lo que debía producir la flota, descuenta gastos por día y detecta rápidamente qué vehículo no trabajó.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3 text-xs font-medium text-slate-500 dark:text-slate-200">
                        <span class="rounded-full border border-gray-200 bg-gray-100 px-3 py-1.5 dark:border-white/10 dark:bg-white/10">Semana domingo a sábado</span>
                        <span class="rounded-full border border-gray-200 bg-gray-100 px-3 py-1.5 dark:border-white/10 dark:bg-white/10">Edición por celda</span>
                        <span class="rounded-full border border-gray-200 bg-gray-100 px-3 py-1.5 dark:border-white/10 dark:bg-white/10">Control por vehículo</span>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/10 dark:backdrop-blur-md">
                        <p class="text-[11px] uppercase tracking-[0.24em] text-slate-500 dark:text-slate-300">Neto semanal</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $this->money($dataset['summary']['neto']) }}</p>
                        <div class="mt-3 text-xs text-slate-500 dark:text-slate-300">
                            @if($dataset['summary']['administracion'] > 0)
                                <span class="text-danger-600 dark:text-danger-300">-{{ $this->money($dataset['summary']['administracion']) }} administración</span>
                            @else
                                Ingreso menos gastos
                            @endif
                        </div>
                    </div>
                        <div class="grid grid-cols-2 gap-4 xl:min-h-[140px]">
                        <div class="rounded-[20px] border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/10 dark:backdrop-blur-md">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500 dark:text-slate-300">Gastos</p>
                            <p class="mt-2 text-xl font-semibold">{{ $this->money($dataset['summary']['gastos']) }}</p>
                        </div>
                        <div class="rounded-[20px] border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/10 dark:backdrop-blur-md">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500 dark:text-slate-300">Administración</p>
                            <p class="mt-2 text-xl font-semibold">{{ $this->money($dataset['summary']['administracion']) }}</p>
                        </div>
                        </div>
                </div>
            </div>
        </section>

        @if(auth()->user()?->hasRole('admin'))
        <div class="rounded-[16px] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900" wire:poll.5s="refreshIfContextChanged">
            <div class="flex flex-wrap items-center gap-2 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3">
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Ver datos de:</span>
                <select wire:model.live="selectedUserId" class="rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950 dark:text-white min-w-[180px] sm:min-w-[220px]">
                    <option value="0">Todos los usuarios</option>
                    @foreach($this->getUsersForSelector() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                @if($selectedUserId > 0)
                    <span class="max-w-[160px] truncate rounded-full bg-primary-50 px-2.5 py-0.5 text-[11px] font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        {{ $this->getSelectedUserName() }}
                    </span>
                @endif
            </div>
        </div>
        @endif

        <section class="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 bg-slate-50/80 px-5 py-1 dark:border-white/10 dark:bg-white/5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Cuadro semanal</h3>
                        <p class="text-sm text-slate-500">Haz clic en una celda para ajustar ingreso, gasto u observación de ese vehículo en ese día.</p>
                    </div>

                    <div class="flex flex-wrap items-end gap-4 xl:gap-6">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Ir a una fecha</span>
                            <div class="relative flex items-center">
                                <input
                                    type="date"
                                    wire:model.live="selectedDate"
                                    class="w-full min-w-[180px] rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-150 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:focus:border-primary-500/50"
                                >
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-2xl border border-gray-200 bg-white p-1 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <button wire:click="previousWeek" type="button" class="flex h-9 items-center justify-center rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white">
                                <span class="mr-1">&larr;</span> Anterior
                            </button>
                            <div class="h-4 w-px bg-gray-200 dark:bg-white/10"></div>
                            <button wire:click="goToCurrentWeek" type="button" class="flex h-9 items-center justify-center rounded-xl bg-primary-500 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:bg-primary-600 dark:hover:bg-primary-500">
                                Semana actual
                            </button>
                            <div class="h-4 w-px bg-gray-200 dark:bg-white/10"></div>
                            <button wire:click="nextWeek" type="button" class="flex h-9 items-center justify-center rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white">
                                Siguiente <span class="ml-1">&rarr;</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100/90 dark:bg-white/5">
                        <tr>
                            <th class="sticky left-0 z-20 min-w-44 border-b border-r border-gray-200 bg-slate-100 px-4 py-4 text-left font-semibold text-slate-900 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                Día
                            </th>
                            @forelse ($dataset['vehiculos'] as $vehiculo)
                                <th class="min-w-52 border-b border-r border-gray-200 px-3 py-3 text-center dark:border-white/10">
                                    @php($color = $vehiculo->getColorClase())
                                        {{-- Fake classes for Tailwind extractor: bg-red-500/80 dark:bg-red-500/50 bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300 bg-orange-500/80 dark:bg-orange-500/50 bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300 bg-amber-500/80 dark:bg-amber-500/50 bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 bg-green-500/80 dark:bg-green-500/50 bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300 bg-emerald-500/80 dark:bg-emerald-500/50 bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 bg-teal-500/80 dark:bg-teal-500/50 bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-300 bg-cyan-500/80 dark:bg-cyan-500/50 bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-300 bg-sky-500/80 dark:bg-sky-500/50 bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300 bg-blue-500/80 dark:bg-blue-500/50 bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 bg-indigo-500/80 dark:bg-indigo-500/50 bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300 bg-violet-500/80 dark:bg-violet-500/50 bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300 bg-purple-500/80 dark:bg-purple-500/50 bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 bg-fuchsia-500/80 dark:bg-fuchsia-500/50 bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-500/20 dark:text-fuchsia-300 bg-pink-500/80 dark:bg-pink-500/50 bg-pink-100 text-pink-700 dark:bg-pink-500/20 dark:text-pink-300 bg-rose-500/80 dark:bg-rose-500/50 bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 --}}
                                    <div class="relative overflow-hidden rounded-[1.25rem] border border-gray-200 bg-white px-3 py-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                                        <div class="absolute inset-x-0 top-0 h-1 bg-{{ $color }}-500/80 dark:bg-{{ $color }}-500/50"></div>
                                        <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-{{ $color }}-100 text-[16px] font-bold tracking-wide text-{{ $color }}-700 dark:bg-{{ $color }}-500/20 dark:text-{{ $color }}-300">
                                            {{ $vehiculo->getTipoVehiculo() === 'moto' ? '🏍️' : '🚗' }}
                                        </div>
                                        <div class="mt-2 text-sm font-bold tracking-tight text-slate-950 dark:text-white">{{ $vehiculo->placa }}</div>
                                        <div class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                                            {{ $vehiculo->personaNombreEn($dataset['weekStart']) ?? 'Sin conductor' }}
                                        </div>
                                        <div class="mt-3 flex w-full flex-col gap-1.5">
                                            <div class="flex w-full items-center justify-between rounded-lg bg-slate-50 px-2.5 py-1.5 text-[11px] border border-gray-100 dark:border-white/5 dark:bg-white/5">
                                                <span class="text-slate-500 dark:text-slate-400">Cuota</span>
                                                <span class="font-semibold text-slate-800 dark:text-gray-200">{{ $this->money($vehiculo->cuotaDiariaEn($dataset['weekStart'])) }}</span>
                                            </div>
                                            <div class="flex w-full items-center justify-between rounded-lg bg-slate-50 px-2.5 py-1.5 text-[11px] border border-gray-100 dark:border-white/5 dark:bg-white/5">
                                                <span class="text-slate-500 dark:text-slate-400">Admin</span>
                                                <span class="font-semibold text-slate-800 dark:text-gray-200">{{ $this->money($vehiculo->administracionEn($dataset['weekStart'])) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                            @empty
                                <th class="border-b border-r border-gray-200 px-4 py-8 text-center text-slate-500 dark:border-white/10 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-heroicon-o-truck class="h-8 w-8 text-gray-300 dark:text-gray-600" />
                                        <span class="text-sm">No hay vehículos activos esta semana.</span>
                                    </div>
                                </th>
                            @endforelse
                            <th class="min-w-36 border-b border-r border-gray-200 px-4 py-4 text-center font-semibold text-danger-600 dark:border-white/10 dark:text-danger-400">
                                Gastos
                            </th>
                            <th class="min-w-36 border-b border-r border-gray-200 px-4 py-4 text-center font-semibold text-success-600 dark:border-white/10 dark:text-success-400">
                                Total día
                            </th>
                            <th class="min-w-40 border-b border-gray-200 px-4 py-4 text-center font-semibold text-slate-900 dark:border-white/10 dark:text-white">
                                Acumulado semana
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataset['rows'] as $row)
                            <tr class="border-b border-gray-200 last:border-b-0 hover:bg-slate-50/80 dark:border-white/10 dark:hover:bg-white/[0.03]">
                                <td class="sticky left-0 z-10 border-r border-gray-200 bg-white px-4 py-4 align-top dark:border-white/10 dark:bg-gray-900">
                                    <div class="font-medium text-slate-950 dark:text-white">{{ $this->dayLabel($row['fecha']) }}</div>
                                    <div class="text-xs text-slate-500">{{ $row['fecha']->format('d/m/Y') }}</div>
                                </td>

                                @foreach ($row['cells'] as $cell)
                                    @php($cellDisabled = ($cell['bloqueado'] ?? false) || ($cell['not_applicable'] ?? false))
                                    <td class="border-r border-gray-200 px-2 py-2 text-center align-top dark:border-white/10">
                                        <button
                                            type="button"
                                            @if(!$cellDisabled) wire:click="openRegistroModal({{ $cell['vehiculo_id'] }}, '{{ $cell['fecha'] }}')" @else disabled @endif
                                            class="relative w-full rounded-2xl border px-3 py-4 text-sm shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary-500/50 {{ ($cell['bloqueado'] ?? false) ? 'border-dashed border-gray-200 bg-gray-50/50 text-gray-400 cursor-not-allowed dark:border-white/5 dark:bg-transparent dark:text-gray-600' : (($cell['not_applicable'] ?? false) ? 'border-dashed border-gray-200 bg-gray-50/50 text-gray-400 cursor-not-allowed dark:border-white/5 dark:bg-transparent dark:text-gray-600' : ((!$cell['trabajo']) ? 'border-danger-100 bg-danger-50/50 text-danger-900 hover:border-danger-300 hover:bg-danger-50 dark:border-danger-500/20 dark:bg-danger-500/5 dark:text-danger-200' : (($cell['gasto'] > 0) ? 'border-warning-200 bg-warning-50/50 text-warning-900 hover:border-warning-300 hover:bg-warning-50 dark:border-warning-500/20 dark:bg-warning-500/5 dark:text-warning-200' : (($cell['has_changes']) ? 'border-primary-100 bg-primary-50/50 text-slate-800 hover:border-primary-300 hover:bg-primary-50 dark:border-primary-500/20 dark:bg-primary-500/5 dark:text-gray-200' : 'border-gray-200 bg-white text-slate-800 hover:border-gray-300 hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10')))) }}"
                                        >
                                            <div class="text-base font-semibold {{ ($cell['not_applicable'] ?? false) || ($cell['bloqueado'] ?? false) ? '' : ((!$cell['trabajo']) ? 'text-danger-600 dark:text-danger-400' : 'text-slate-900 dark:text-white') }}">{{ ($cell['not_applicable'] ?? false) ? '—' : $this->money($cell['ingreso']) }}</div>
                                            @if ($cell['bloqueado'] ?? false)
                                                <div class="mt-1 text-[10px] font-medium text-gray-400">{{ ($cell['estado'] ?? '') === 'mantenimiento' ? 'Mantenimiento' : 'Inactivado' }}</div>
                                            @endif
                                            @if (($cell['administracion'] ?? 0) > 0)
                                                <div class="mt-1 text-[10px] font-medium text-slate-500">Admin: {{ $this->money($cell['administracion'] ?? 0) }}</div>
                                            @endif
                                            @if ($cell['gasto'] > 0)
                                                <div class="mt-1 text-xs font-medium text-danger-600 dark:text-danger-400">Gasto: {{ $this->money($cell['gasto']) }}</div>
                                                @if ($cell['categoria_gasto'] && strlen($cell['categoria_gasto']) > 0)
                                                    @php($categoria = $cell['categoria_gasto'])
                                                    @php($colors = [
                                                        'daño' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-300',
                                                        'mantenimiento' => 'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-300',
                                                        'multa' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
                                                        'otro' => 'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300'
                                                    ])
                                                    @php($labels = [
                                                        'daño' => '🛠️ Daño',
                                                        'mantenimiento' => '🔧 Mantenimiento',
                                                        'multa' => '🚫 Multa',
                                                        'otro' => '📋 Otro'
                                                    ])
                                                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $colors[$categoria] ?? $colors['otro'] }}">
                                                        {{ $labels[$categoria] ?? '📋 Otro' }}
                                                    </span>
                                                @endif
                                            @endif
                                            @if (! $cell['trabajo'] && ! ($cell['not_applicable'] ?? false))
                                                <div class="mt-1 text-xs font-medium">No trabajó</div>
                                            @endif
                                            @if ($cell['observaciones'])
                                                <div class="mt-2 line-clamp-2 text-[11px] opacity-80">{{ $cell['observaciones'] }}</div>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach

                                <td class="border-r border-gray-200 px-4 py-4 text-right font-semibold text-danger-600 dark:border-white/10 dark:text-danger-400">
                                    {{ $this->money($row['gastos']) }}
                                </td>
                                <td class="border-r border-gray-200 px-4 py-4 text-right font-semibold {{ $row['total'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }} dark:border-white/10">
                                    {{ $this->money($row['total']) }}
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-900 dark:text-white">
                                    {{ $this->money($row['acumulado']) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-slate-100/70 shadow-sm dark:bg-white/5">
                            <td class="sticky left-0 z-10 border-r border-t border-gray-200 bg-slate-100 px-4 py-6 align-top font-semibold text-slate-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                Total semanal
                                <div class="mt-1 text-xs font-normal text-slate-500">Resumen por vehículo</div>
                            </td>

                            @foreach ($dataset['vehiculos'] as $vehiculo)
                                @php($totalVehiculo = $dataset['vehicleTotals'][$vehiculo->id] ?? ['real' => 0, 'gastos' => 0, 'neto' => 0])
                                <td class="border-r border-t border-gray-200 px-4 py-6 text-center dark:border-white/10">
                                    <div class="text-base font-semibold {{ $totalVehiculo['neto'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                        {{ $this->money($totalVehiculo['neto']) }}
                                    </div>
                                    <div class="mt-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                                        Ingreso: {{ $this->money($totalVehiculo['real']) }}
                                    </div>
                                    <div class="mt-1 text-xs font-medium text-danger-600/80 dark:text-danger-400/80">
                                        Gastos: {{ $this->money($totalVehiculo['gastos']) }}
                                    </div>
                                </td>
                            @endforeach

                            <td class="border-r border-t border-gray-200 px-4 py-6 text-right text-base font-bold text-danger-600 dark:border-white/10 dark:text-danger-400">
                                {{ $this->money($dataset['summary']['gastos']) }}
                            </td>
                            <td class="border-r border-t border-gray-200 px-4 py-6 text-right text-base font-bold {{ $dataset['summary']['neto'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }} dark:border-white/10">
                                {{ $this->money($dataset['summary']['neto']) }}
                            </td>
                            <td class="border-t border-gray-200 px-4 py-6 text-right text-base font-bold text-slate-950 dark:border-white/10 dark:text-white">
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
                    <article class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Esperado semanal</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $this->money($dataset['summary']['esperado']) }}</p>
                        <p class="mt-2 text-xs text-slate-500">Suma de cuotas sin ajustes</p>
                    </article>
                    <article class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Ingreso ajustado</p>
                        <p class="mt-2 text-2xl font-semibold text-primary-600">{{ $this->money($dataset['summary']['real']) }}</p>
                        <p class="mt-2 text-xs text-slate-500">Incluye días sin trabajar y cambios manuales</p>
                    </article>
                    <article class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Gastos cargados</p>
                        <p class="mt-2 text-2xl font-semibold text-danger-600">{{ $this->money($dataset['summary']['gastos']) }}</p>
                        <p class="mt-2 text-xs text-slate-500">Descuentos semanales registrados</p>
                    </article>
                    <article class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="text-sm text-slate-500">Vehículos activos</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $dataset['vehiculos']->count() }}</p>
                        <p class="mt-2 text-xs text-slate-500">Columnas visibles esta semana</p>
                    </article>
                </section>
            </div>

            <aside class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 2xl:sticky 2xl:top-6 2xl:self-start">
                <div>
                    <p class="text-sm font-medium text-slate-500">Historial</p>
                    <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Últimas 12 semanas</h3>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($history as $week)
                        <button
                            type="button"
                            wire:click="$set('selectedDate', '{{ $week['week_start'] }}')"
                            class="w-full rounded-[22px] border px-4 py-4 text-left transition {{ $week['is_selected'] ? 'border-gray-900 bg-slate-950 text-white dark:border-gray-600 dark:bg-gray-500/10' : 'border-gray-200 hover:border-gray-400 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5' }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold {{ $week['is_selected'] ? 'text-white' : 'text-slate-950 dark:text-white' }}">
                                        {{ \Carbon\Carbon::parse($week['week_start'])->format('d/m') }} - {{ \Carbon\Carbon::parse($week['week_end'])->format('d/m') }}
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
                            <div class="mt-3 grid grid-cols-4 gap-1.5 text-[11px]">
                                <div class="rounded-2xl px-2 py-2 {{ $week['is_selected'] ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200' }}">
                                    Esp: {{ $this->money($week['esperado']) }}
                                </div>
                                <div class="rounded-2xl px-2 py-2 {{ $week['is_selected'] ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200' }}">
                                    Ing: {{ $this->money($week['real']) }}
                                </div>
                                <div class="rounded-2xl px-2 py-2 {{ $week['is_selected'] ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200' }}">
                                    Gas: {{ $this->money($week['gastos']) }}
                                </div>
                                <div class="rounded-2xl px-2 py-2 {{ $week['is_selected'] ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200' }}">
                                    Adm: {{ $this->money($week['administracion'] ?? 0) }}
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-[22px] border border-dashed border-gray-300 px-4 py-6 text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                            Aun no hay semanas anteriores con registros guardados.
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>

        <div
            x-data="{ open: @entangle('isModalOpen') }"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl dark:bg-gray-900"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            >
                <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold">Ajustar registro semanal</h3>
                            <p class="text-sm text-gray-500">
                                {{ $this->selectedVehiculo()?->placa ?? $this->selectedVehiculo()['placa'] ?? '' }}
                                @if ($this->selectedVehiculo()?->persona?->nombre ?? $this->selectedVehiculo()['persona_nombre'] ?? false)
                                    · {{ $this->selectedVehiculo()?->persona?->nombre ?? $this->selectedVehiculo()['persona_nombre'] ?? '' }}
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
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-white/10 dark:bg-gray-950 dark:disabled:bg-white/5"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Gasto del día</span>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model.live="modalForm.gasto"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950"
                            >
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Administración</span>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model="modalForm.administracion"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950"
                        >
                    </label>

                    <div x-data="{ gasto: {{ (float) $modalForm['gasto'] }} }" x-init="$watch('$wire.modalForm.gasto', value => gasto = parseFloat(value || 0))">
                        <label class="block" x-show="gasto > 0" x-transition.opacity.duration.200ms>
                            <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Categoría del gasto</span>
                            <select
                                wire:model="modalForm.categoria_gasto"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950"
                            >
                                <option value="daño">Daño</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="multa">Multa</option>
                                <option value="otro">Otro</option>
                            </select>
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Observaciones</span>
                        <textarea
                            wire:model="modalForm.observaciones"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950"
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
    </div>
</x-filament-panels::page>
