<?php

namespace App\Filament\Resources\Gastos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GastoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('vehiculo_id')
                    ->numeric(),
                TextEntry::make('persona_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('fecha')
                    ->date(),
                TextEntry::make('categoria')
                    ->badge(),
                TextEntry::make('valor')
                    ->numeric(),
                TextEntry::make('detalle')
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
