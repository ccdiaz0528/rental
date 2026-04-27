<?php

namespace App\Filament\Resources\PagoDiarios\Pages;

use App\Filament\Resources\PagoDiarios\PagoDiarioResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPagoDiario extends EditRecord
{
    protected static string $resource = PagoDiarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
