<?php

namespace App\Filament\Resources\Gastos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class GastoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('vehiculo_id')
                    ->required()
                    ->numeric(),
                TextInput::make('persona_id')
                    ->numeric(),
                DatePicker::make('fecha')
                    ->required(),
                Select::make('categoria')
                    ->options([
            'mantenimiento' => 'Mantenimiento',
            'aceite' => 'Aceite',
            'lavado' => 'Lavado',
            'tanqueada' => 'Tanqueada',
            'llantas' => 'Llantas',
            'frenos' => 'Frenos',
            'electrico' => 'Electrico',
            'multa' => 'Multa',
            'fotomulta' => 'Fotomulta',
            'prestamo' => 'Prestamo',
            'otro' => 'Otro',
        ])
                    ->required(),
                TextInput::make('valor')
                    ->required()
                    ->numeric(),
                Textarea::make('detalle')
                    ->columnSpanFull(),
            ]);
    }
}
