<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VehiculoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('placa'),
                TextEntry::make('marca')
                    ->placeholder('-'),
                TextEntry::make('modelo')
                    ->placeholder('-'),
                TextEntry::make('anio')
                    ->placeholder('-'),
                TextEntry::make('color')
                    ->placeholder('-'),
                TextEntry::make('persona.nombre')
                    ->label('Conductor')
                    ->placeholder('Sin conductor'),
                TextEntry::make('cuota_diaria')
                    ->numeric(),
                TextEntry::make('estado')
                    ->badge(),
                TextEntry::make('observaciones')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
