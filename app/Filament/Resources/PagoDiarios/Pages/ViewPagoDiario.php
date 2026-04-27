<?php

namespace App\Filament\Resources\PagoDiarios\Pages;

use App\Filament\Resources\PagoDiarios\PagoDiarioResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPagoDiario extends ViewRecord
{
    protected static string $resource = PagoDiarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
