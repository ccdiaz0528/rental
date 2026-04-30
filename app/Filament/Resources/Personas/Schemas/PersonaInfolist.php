<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PersonaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre')
                    ->label('Nombre')
                    ->icon('heroicon-o-user'),
                TextEntry::make('cedula')
                    ->label('Cédula')
                    ->icon('heroicon-o-identification')
                    ->placeholder('-'),
                TextEntry::make('telefono')
                    ->label('Teléfono')
                    ->icon('heroicon-o-phone')
                    ->placeholder('-'),
                TextEntry::make('direccion')
                    ->label('Dirección')
                    ->icon('heroicon-o-map-pin')
                    ->placeholder('-'),
                TextEntry::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->placeholder('Sin observaciones')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
