<?php

namespace App\Filament\Resources\PagoDiarios\Pages;

use App\Filament\Resources\PagoDiarios\PagoDiarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPagoDiarios extends ListRecords
{
    protected static string $resource = PagoDiarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
