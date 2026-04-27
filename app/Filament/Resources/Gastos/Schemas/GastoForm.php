<?php

namespace App\Filament\Resources\Gastos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GastoForm
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
                ->label('Persona responsable')
                ->relationship('persona', 'nombre')
                ->nullable()
                ->searchable()
                ->preload(),

            DatePicker::make('fecha')
                ->label('Fecha')
                ->required()
                ->default(now()),

            Select::make('categoria')
                ->label('Categoría')
                ->options([
                    'mantenimiento' => 'Mantenimiento',
                    'aceite'        => 'Cambio de aceite',
                    'lavado'        => 'Lavado',
                    'tanqueada'     => 'Tanqueada',
                    'mecanica'      => 'Mecánica',
                    'llantas'       => 'Llantas',
                    'fotomulta'     => 'Fotomulta',
                    'prestamo'      => 'Préstamo',
                    'otro'          => 'Otro',
                ])
                ->required(),

            TextInput::make('valor')
                ->label('Valor')
                ->numeric()
                ->prefix('$')
                ->required(),

            Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
