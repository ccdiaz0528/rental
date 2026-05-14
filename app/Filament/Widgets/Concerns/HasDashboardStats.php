<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Support\Collection;

trait HasDashboardStats
{
    protected function money(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }

    protected function gastosPorCategoria(Collection $registros): array
    {
        $gastos = ['daño' => 0.0, 'mantenimiento' => 0.0, 'multa' => 0.0, 'otro' => 0.0];

        foreach ($registros as $r) {
            $cat = $r->categoria_gasto ?: 'otro';
            $gastos[$cat] += (float) ($r->gasto ?? 0);
        }

        return $gastos;
    }
}
