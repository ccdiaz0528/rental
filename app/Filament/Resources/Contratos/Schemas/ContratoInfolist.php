<?php

namespace App\Filament\Resources\Contratos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContratoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('vehiculo.placa')
                    ->label('Vehículo'),
                TextEntry::make('persona.nombre')
                    ->label('Conductor'),
                TextEntry::make('tipo')
                    ->badge(),
                TextEntry::make('fecha_inicio')
                    ->date(),
                TextEntry::make('fecha_fin')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('valor_diario')
                    ->numeric(),
                TextEntry::make('estado')
                    ->badge(),
                TextEntry::make('documento')
                    ->label('Documento')
                    ->formatStateUsing(fn ($record) => $record->documento ? '📎 Ver documento' : '-')
                    ->url(fn ($record) => $record->documento ? route('contrato.documento', ['path' => ltrim($record->documento, '/')]) : null, true),
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
