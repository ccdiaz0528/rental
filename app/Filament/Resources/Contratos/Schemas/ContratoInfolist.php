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
                    ->label('Vehículo')
                    ->icon('heroicon-o-truck'),
                TextEntry::make('persona.nombre')
                    ->label('Conductor')
                    ->icon('heroicon-o-user'),
                TextEntry::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                TextEntry::make('valor_diario')
                    ->label('Valor diario')
                    ->icon('heroicon-o-currency-dollar')
                    ->money('COP'),
                TextEntry::make('fecha_inicio')
                    ->label('Fecha inicio'),
                TextEntry::make('fecha_fin')
                    ->label('Fecha fin')
                    ->placeholder('Sin fecha'),
                TextEntry::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextEntry::make('documento')
                    ->label('Documento')
                    ->formatStateUsing(fn ($record) => $record->documento ? '📎 Ver documento' : '-')
                    ->url(fn ($record) => $record->documento ? route('contrato.documento', ['path' => ltrim($record->documento, '/')]) : null, true),
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
