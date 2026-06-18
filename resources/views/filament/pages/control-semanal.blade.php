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

    <div class="space-y-6 tabular-nums">
        <section class="overflow-hidden rounded-[32px] border border-gray-200 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.10),_transparent_28%),linear-gradient(135deg,#ffffff_0%,#f8fafc_55%,#f1f5f9_100%)] text-slate-900 shadow-[0_8px_30px_rgba(0,0,0,0.06)] dark:border-white/10 dark:bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.22),_transparent_28%),linear-gradient(135deg,#0f172a_0%,#111827_55%,#1e293b_100%)] dark:text-white dark:shadow-[0_30px_80px_rgba(15,23,42,0.28)]">
            <div class="grid gap-8 px-7 py-7 xl:grid-cols-[minmax(0,1fr)_26rem] xl:items-stretch">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:border-white/15 dark:bg-white/10 dark:text-cyan-100 dark:backdrop-blur-sm">
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
                                <span class="text-rose-600 dark:text-rose-300">-{{ $this->money($dataset['summary']['administracion']) }} administración</span>
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
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-300">Ir a una fecha</span>
                            <input
                                type="date"
                                wire:model.live="selectedDate"
                                class="rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950 dark:text-white min-w-[180px]"
                            >
                        </div>

                        <div class="flex items-center gap-2">
                            <button wire:click="previousWeek" type="button" class="flex items-center gap-1 rounded-2xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">
                                <span>‹</span>
                                <span>Anterior</span>
                            </button>
                            <button wire:click="goToCurrentWeek" type="button" class="rounded-2xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 dark:bg-primary-600 dark:hover:bg-primary-500">
                                Semana actual
                            </button>
                            <button wire:click="nextWeek" type="button" class="flex items-center gap-1 rounded-2xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">
                                <span>Siguiente</span>
                                <span>›</span>
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
                                @php($palette = $headerPalettes[$loop->index % count($headerPalettes)])
                                <th class="min-w-52 border-b border-r border-gray-200 px-3 py-3 text-center dark:border-white/10">
                                    <div class="rounded-[20px] {{ $palette['soft'] }} px-3 py-3">
                                        <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full {{ $palette['bg'] }} text-xs font-bold text-white">
                                            {{ strtoupper(substr($vehiculo->placa, 0, 2)) }}
                                        </div>
                                        <div class="mt-3 text-sm font-semibold text-slate-950 dark:text-white">{{ $vehiculo->placa }}</div>
                                        <div class="mt-1 text-xs font-normal text-slate-500">
                                            {{ $vehiculo->personaNombreEn($dataset['weekStart']) ?? 'Sin conductor' }}
                                        </div>
                                        <div class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $palette['soft'] }} {{ $palette['text'] }} ring-1 ring-inset ring-black/5 dark:ring-white/10">
                                            Cuota {{ $this->money($vehiculo->cuotaDiariaEn($dataset['weekStart'])) }}
                                        </div>
                                        <div class="mt-1.5 inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $palette['soft'] }} {{ $palette['text'] }} ring-1 ring-inset ring-black/5 dark:ring-white/10 opacity-75">
                                            Admin {{ $this->money($vehiculo->administracionEn($dataset['weekStart'])) }}
                                        </div>
                                    </div>
                                </th>
                            @empty
                                <th class="border-b border-r border-gray-200 px-4 py-3 text-left dark:border-white/10">No hay vehículos activos</th>
                            @endforelse
                            <th class="min-w-36 border-b border-r border-gray-200 px-4 py-4 text-center font-semibold text-danger-700 dark:border-white/10 dark:text-danger-300">
                                Gastos
                            </th>
                            <th class="min-w-36 border-b border-r border-gray-200 px-4 py-4 text-center font-semibold text-success-700 dark:border-white/10 dark:text-success-300">
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
                                            class="w-full rounded-[20px] border px-3 py-4 text-sm shadow-sm transition {{ ($cell['bloqueado'] ?? false) ? 'border-gray-200 bg-gray-100/70 text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-500' : (($cell['not_applicable'] ?? false) ? 'border-dashed border-gray-300 bg-gray-50/50 text-gray-400 dark:border-gray-600 dark:bg-white/[0.03] dark:text-gray-500' : ((!$cell['trabajo']) ? 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300' : (($cell['gasto'] > 0) ? 'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300' : (($cell['has_changes']) ? 'border-gray-300 bg-gray-50 text-gray-700 dark:border-gray-500/30 dark:bg-gray-500/10 dark:text-gray-300' : 'border-gray-200 bg-white text-slate-800 hover:-translate-y-0.5 hover:border-gray-400 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100 dark:hover:bg-white/10')))) }}"
                                        >
                                            <div class="text-base font-semibold">{{ ($cell['not_applicable'] ?? false) ? '—' : $this->money($cell['ingreso']) }}</div>
                                            @if ($cell['bloqueado'] ?? false)
                                                <div class="mt-1 text-[10px] font-medium text-gray-400">{{ ($cell['estado'] ?? '') === 'mantenimiento' ? 'Mantenimiento' : 'Inactivado' }}</div>
                                            @endif
                                            @if (($cell['administracion'] ?? 0) > 0)
                                                <div class="mt-1 text-[10px] font-medium text-slate-500">Admin: {{ $this->money($cell['administracion'] ?? 0) }}</div>
                                            @endif
                                            @if ($cell['gasto'] > 0)
                                                <div class="mt-1 text-xs font-medium">Gasto: {{ $this->money($cell['gasto']) }}</div>
                                                @if ($cell['categoria_gasto'] && strlen($cell['categoria_gasto']) > 0)
                                                    @php($categoria = $cell['categoria_gasto'])
                                                    @php($colors = [
                                                        'daño' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-300',
                                                        'mantenimiento' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                                                        'multa' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                                                        'otro' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300'
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

                                <td class="border-r border-gray-200 px-4 py-4 text-right font-semibold text-danger-600 dark:border-white/10">
                                    {{ $this->money($row['gastos']) }}
                                </td>
                                <td class="border-r border-gray-200 px-4 py-4 text-right font-semibold {{ $row['total'] >= 0 ? 'text-success-600' : 'text-danger-600' }} dark:border-white/10">
                                    {{ $this->money($row['total']) }}
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-900 dark:text-white">
                                    {{ $this->money($row['acumulado']) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-slate-100/70 dark:bg-white/5">
                            <td class="sticky left-0 z-10 border-r border-t border-gray-200 bg-slate-100 px-4 py-4 align-top font-semibold text-slate-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                Total semanal
                                <div class="mt-1 text-xs font-normal text-slate-500">Resumen por vehículo</div>
                            </td>

                            @foreach ($dataset['vehiculos'] as $vehiculo)
                                @php($totalVehiculo = $dataset['vehicleTotals'][$vehiculo->id] ?? ['real' => 0, 'gastos' => 0, 'neto' => 0])
                                <td class="border-r border-t border-gray-200 px-4 py-4 text-center dark:border-white/10">
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

                            <td class="border-r border-t border-gray-200 px-4 py-4 text-right font-semibold text-danger-600 dark:border-white/10">
                                {{ $this->money($dataset['summary']['gastos']) }}
                            </td>
                            <td class="border-r border-t border-gray-200 px-4 py-4 text-right font-semibold {{ $dataset['summary']['neto'] >= 0 ? 'text-success-600' : 'text-danger-600' }} dark:border-white/10">
                                {{ $this->money($dataset['summary']['neto']) }}
                            </td>
                            <td class="border-t border-gray-200 px-4 py-4 text-right font-semibold text-slate-950 dark:border-white/10 dark:text-white">
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
