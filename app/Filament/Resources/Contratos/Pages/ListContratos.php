<?php

namespace App\Filament\Resources\Contratos\Pages;

use App\Filament\Resources\Contratos\ContratoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContratos extends ListRecords
{
    protected static string $resource = ContratoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['vehiculo' => fn ($q) => $q->withTrashed(), 'persona']);
    }
}
