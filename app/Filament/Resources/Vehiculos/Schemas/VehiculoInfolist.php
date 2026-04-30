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
                TextEntry::make('placa')
                    ->label('Placa')
                    ->icon('heroicon-o-identification'),
                TextEntry::make('marca')
                    ->label('Marca')
                    ->icon('heroicon-o-tag')
                    ->placeholder('-'),
                TextEntry::make('modelo')
                    ->label('Modelo')
                    ->icon('heroicon-o-cube')
                    ->placeholder('-'),
                TextEntry::make('anio')
                    ->label('Año')
                    ->icon('heroicon-o-calendar')
                    ->placeholder('-'),
                TextEntry::make('color')
                    ->label('Color')
                    ->icon('heroicon-o-paint-brush')
                    ->placeholder('-'),

                TextEntry::make('fecha_vencimiento_soat')
                    ->label('Vencimiento SOAT')
                    ->icon('heroicon-o-shield-check')
                    ->date(),

                TextEntry::make('fecha_vencimiento_tecnomecanico')
                    ->label('Vencimiento Tecnomecánica')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->date(),

                TextEntry::make('persona.nombre')
                    ->label('Conductor')
                    ->icon('heroicon-o-user')
                    ->placeholder('Sin conductor'),
                TextEntry::make('cuota_diaria')
                    ->label('Cuota diaria')
                    ->icon('heroicon-o-currency-dollar')
                    ->money('COP'),
                TextEntry::make('estado')
                    ->label('Estado')
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
