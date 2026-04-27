<?php

namespace App\Filament\Resources\Contratos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ContratoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('vehiculo_id')
                    ->required()
                    ->numeric(),
                TextInput::make('persona_id')
                    ->required()
                    ->numeric(),
                Select::make('tipo')
                    ->options(['alquiler' => 'Alquiler', 'opcion_compra' => 'Opcion compra'])
                    ->default('alquiler')
                    ->required(),
                DatePicker::make('fecha_inicio')
                    ->required(),
                DatePicker::make('fecha_fin'),
                TextInput::make('valor_diario')
                    ->required()
                    ->numeric(),
                Select::make('estado')
                    ->options(['activo' => 'Activo', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'])
                    ->default('activo')
                    ->required(),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ]);
    }
}
