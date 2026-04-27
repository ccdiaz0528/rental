<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VehiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('placa')
                    ->required(),
                TextInput::make('marca'),
                TextInput::make('modelo'),
                TextInput::make('anio'),
                TextInput::make('color'),
                TextInput::make('cuota_diaria')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('estado')
                    ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo', 'mantenimiento' => 'Mantenimiento'])
                    ->default('activo')
                    ->required(),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ]);
    }
}
