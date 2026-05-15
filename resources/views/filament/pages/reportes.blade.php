<x-filament-panels::page>
    @php($resumen = $this->getResumen())
    @php($gastosCat = $this->getGastosPorCategoria())
    @php($detalleVehiculos = $this->getDetallePorVehiculo())
    @php($detalleDiario = $this->getDetalleDiario())

    <div class="flex flex-col gap-10">
        <section class="overflow-hidden rounded-[32px] border border-gray-300 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.22),_transparent_28%),linear-gradient(135deg,#0f172a_0%,#111827_55%,#1e293b_100%)] text-white shadow-[0_30px_80px_rgba(15,23,42,0.28)] dark:border-white/10">
            <div class="grid gap-8 px-7 py-7 xl:grid-cols-[minmax(0,1fr)_26rem] xl:items-stretch">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-100 backdrop-blur-sm">
                        {{ $this->getPeriodoLabel() }}
                    </div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight lg:text-4xl">
                        {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200/90">
                        Consulta ingresos, gastos y rentabilidad de la flota para el período seleccionado.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-[24px] border border-white/10 bg-white/10 p-5 backdrop-blur-md xl:min-h-[140px]">
                        <p class="text-[11px] uppercase tracking-[0.24em] text-slate-300">Neto del período</p>
                        <p class="mt-2 text-3xl font-semibold {{ $resumen['neto'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                            {{ $this->money($resumen['neto']) }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 xl:min-h-[140px]">
                        <div class="rounded-[20px] border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-300">Gastos</p>
                            <p class="mt-2 text-xl font-semibold">{{ $this->money($resumen['gastos']) }}</p>
                        </div>
                        <div class="rounded-[20px] border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-300">Ingresos</p>
                            <p class="mt-2 text-xl font-semibold">{{ $this->money($resumen['real']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if(auth()->user()?->hasRole('admin'))
        <div class="rounded-[16px] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900" wire:poll.5s="refreshIfContextChanged">
            <div class="flex items-center gap-4 px-5 py-3">
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Ver datos de:</span>
                <select wire:model.live="selectedUserId" class="rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950 dark:text-white min-w-[220px]">
                    <option value="0">Todos los usuarios</option>
                    @foreach($this->getUsersForSelector() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                @if($selectedUserId > 0)
                    <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-[11px] font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        {{ $this->getSelectedUserName() }}
                    </span>
                @endif
            </div>
        </div>
        @endif

        <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-wrap items-end gap-6">
                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-300">Período</span>
                    <select wire:model.live="periodo" class="rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950 dark:text-white min-w-[200px]">
                        <option value="hoy">Hoy</option>
                        <option value="ayer">Ayer</option>
                        <option value="esta_semana">Esta semana</option>
                        <option value="semana_pasada">Semana pasada</option>
                        <option value="este_mes">Este mes</option>
                        <option value="mes_pasado">Mes pasado</option>
                        <option value="este_trimestre">Este trimestre</option>
                        <option value="este_semestre">Este semestre</option>
                        <option value="este_anio">Este año</option>
                        <option value="anio_pasado">Año pasado</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>

                @if($periodo === 'personalizado')
                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-300">Desde</span>
                    <input type="date" wire:model.live="fechaInicio" class="rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950 dark:text-white">
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-300">Hasta</span>
                    <input type="date" wire:model.live="fechaFin" class="rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950 dark:text-white">
                </div>
                @endif
            </div>
        </section>

        <section class="grid gap-8 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-slate-500">Esperado</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $this->money($resumen['esperado']) }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $resumen['dias'] }} días · Cuotas base sin ajustes</p>
            </article>
            <article class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-slate-500">Ingreso real</p>
                <p class="mt-2 text-2xl font-semibold text-primary-600">{{ $this->money($resumen['real']) }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $resumen['total_registros_modificados'] }} registros con cambios</p>
            </article>
            <article class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-slate-500">Gastos</p>
                <p class="mt-2 text-2xl font-semibold text-danger-600">{{ $this->money($resumen['gastos']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Descuentos del período</p>
            </article>
            <article class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-slate-500">Neto</p>
                <p class="mt-2 text-2xl font-semibold {{ $resumen['neto'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $this->money($resumen['neto']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Ingreso real − Gastos</p>
            </article>
        </section>

        <div class="grid gap-8 xl:grid-cols-2">
            <section class="rounded-[24px] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Gastos por categoría</h3>
                    <p class="text-sm text-slate-500">Distribución de gastos registrados</p>
                </div>

                <div class="p-5">
                    @if($gastosCat['total'] > 0)
                        @php($categorias = ['daño' => ['label' => 'Daño', 'color' => 'bg-danger-500'], 'mantenimiento' => ['label' => 'Mantenimiento', 'color' => 'bg-blue-500'], 'multa' => ['label' => 'Multa', 'color' => 'bg-amber-500'], 'otro' => ['label' => 'Otro', 'color' => 'bg-slate-500']])
                        <div class="space-y-3">
                            @foreach($categorias as $key => $cat)
                                @php($valor = $gastosCat['categorias'][$key] ?? 0)
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ $cat['label'] }}</span>
                                        <span class="text-slate-500">{{ $this->money($valor) }} ({{ $this->porcentaje($valor, $gastosCat['total']) }})</span>
                                    </div>
                                    <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                        <div class="h-full rounded-full {{ $cat['color'] }}" style="width: {{ $gastosCat['total'] > 0 ? ($valor / $gastosCat['total']) * 100 : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 border-t border-gray-100 pt-3 dark:border-white/5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-slate-700 dark:text-slate-300">Total gastos</span>
                                <span class="font-semibold text-danger-600">{{ $this->money($gastosCat['total']) }}</span>
                            </div>
                        </div>
                    @else
                        <p class="py-6 text-center text-sm text-slate-400">Sin gastos registrados en este período.</p>
                    @endif
                </div>
            </section>

            <section class="rounded-[24px] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Desglose por vehículo</h3>
                    <p class="text-sm text-slate-500">Rentabilidad individual</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Vehículo</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">Ingresos</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">Gastos</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">Neto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detalleVehiculos as $dv)
                                <tr class="border-t border-gray-100 dark:border-white/5 hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-950 dark:text-white">{{ $dv['placa'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $dv['conductor'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700 dark:text-slate-300">{{ $this->money($dv['real']) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-danger-600">{{ $this->money($dv['gastos']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $dv['neto'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $this->money($dv['neto']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">Sin vehículos disponibles.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="rounded-[24px] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Detalle diario</h3>
                <p class="text-sm text-slate-500">Registro día por día del período</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Fecha</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">Ingresos</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">Gastos</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">Neto</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">Registros</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($acumulado = 0)
                        @forelse($detalleDiario as $dia)
                            @php($acumulado += $dia['neto'])
                            <tr class="border-t border-gray-100 dark:border-white/5 hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-950 dark:text-white">{{ $dia['fecha']->format('d/m/Y') }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-slate-700 dark:text-slate-300">{{ $this->money($dia['real']) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-danger-600">{{ $this->money($dia['gastos']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $dia['neto'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $this->money($dia['neto']) }}</td>
                                <td class="px-4 py-3 text-right text-xs text-slate-500">{{ $dia['registros'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">Sin registros en este período.</td>
                            </tr>
                        @endforelse
                        @if(count($detalleDiario) > 0)
                            <tr class="border-t-2 border-gray-200 bg-slate-50 font-semibold dark:border-white/10 dark:bg-white/5">
                                <td class="px-4 py-3 text-slate-950 dark:text-white">Total período</td>
                                <td class="px-4 py-3 text-right text-primary-600">{{ $this->money(collect($detalleDiario)->sum('real')) }}</td>
                                <td class="px-4 py-3 text-right text-danger-600">{{ $this->money(collect($detalleDiario)->sum('gastos')) }}</td>
                                <td class="px-4 py-3 text-right {{ $acumulado >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $this->money($acumulado) }}</td>
                                <td class="px-4 py-3 text-right text-slate-500">{{ collect($detalleDiario)->sum('registros') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
