<?php

namespace App\Console\Commands;

use App\Models\Vehiculo;
use App\Models\VehiculoHistorial;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description as ArtisanDescription;
use Illuminate\Console\Attributes\Signature as ArtisanSignature;
use Illuminate\Console\Command;

#[ArtisanSignature('vehiculo:backfill-historial')]
#[ArtisanDescription('Create initial vehiculo_historial records for existing vehicles')]
class VehiculoBackfillHistorial extends Command
{
    public function handle(): void
    {
        $vehiculos = Vehiculo::query()->withTrashed()->with('contratos')->get();

        $bar = $this->output->createProgressBar($vehiculos->count());
        $bar->start();

        foreach ($vehiculos as $vehiculo) {
            $exists = VehiculoHistorial::query()
                ->where('vehiculo_id', $vehiculo->id)
                ->exists();

            if ($exists) {
                $bar->advance();

                continue;
            }

            $fechaInicio = $vehiculo->contratos->min('fecha_inicio');
            $fechaInicio = $fechaInicio
                ? Carbon::parse($fechaInicio)->startOfDay()
                : $vehiculo->created_at;

            VehiculoHistorial::query()->create([
                'vehiculo_id' => $vehiculo->id,
                'persona_id' => $vehiculo->persona_id,
                'cuota_diaria' => $vehiculo->cuota_diaria,
                'administracion' => $vehiculo->administracion ?? 0,
                'fecha_inicio' => $fechaInicio,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Historial created for vehicles without existing records.');
    }
}
