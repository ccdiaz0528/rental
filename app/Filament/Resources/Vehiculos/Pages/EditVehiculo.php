<?php

namespace App\Filament\Resources\Vehiculos\Pages;

use App\Filament\Resources\Vehiculos\VehiculoResource;
use App\Models\Vehiculo;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVehiculo extends EditRecord
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->disabled(fn (Vehiculo $record): bool => ! $record->canBeDeleted())
                ->tooltip(fn (Vehiculo $record): ?string => $record->canBeDeleted()
                    ? null
                    : 'No se puede eliminar porque tiene '.$record->deletionBlockers().'.'),
        ];
    }
}
