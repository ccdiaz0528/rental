<?php

namespace App\Filament\Resources\Contratos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContratoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vehiculo_id')
                ->label('Vehículo')
                ->relationship('vehiculo', 'placa')
                ->required()
                ->searchable()
                ->preload(),

            Select::make('persona_id')
                ->label('Conductor / Persona')
                ->relationship('persona', 'nombre')
                ->required()
                ->searchable()
                ->preload(),

            Select::make('tipo')
                ->label('Tipo de contrato')
                ->options([
                    'alquiler'       => 'Alquiler',
                    'opcion_compra'  => 'Opción de compra',
                ])
                ->required()
                ->default('alquiler'),

            TextInput::make('valor_diario')
                ->label('Valor diario')
                ->numeric()
                ->prefix('$')
                ->required(),

            DatePicker::make('fecha_inicio')
                ->label('Fecha inicio')
                ->required()
                ->default(now()),

            DatePicker::make('fecha_fin')
                ->label('Fecha fin')
                ->nullable(),

            Select::make('estado')
                ->label('Estado')
                ->options([
                    'activo'     => 'Activo',
                    'finalizado' => 'Finalizado',
                    'cancelado'  => 'Cancelado',
                ])
                ->required()
                ->default('activo'),

            Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
