<?php

namespace App\Filament\Resources\PagoDiarios\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PagoDiarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vehiculo_id')
                ->label('Vehículo')
                ->relationship('vehiculo', 'placa')
                ->required(),

            Select::make('persona_id')
                ->label('Conductor')
                ->relationship('persona', 'nombre')
                ->required(),

            Select::make('contrato_id')
                ->label('Contrato')
                ->relationship('contrato', 'id')
                ->required(),

            DatePicker::make('fecha')
                ->label('Fecha')
                ->required()
                ->default(now()),

            TextInput::make('valor')
                ->label('Valor pagado')
                ->numeric()
                ->prefix('$')
                ->required(),

            Select::make('estado')
                ->label('Estado')
                ->options([
                    'pagado'    => 'Pagado',
                    'pendiente' => 'Pendiente',
                    'debe'      => 'Debe',
                ])
                ->required()
                ->default('pagado'),

            Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
