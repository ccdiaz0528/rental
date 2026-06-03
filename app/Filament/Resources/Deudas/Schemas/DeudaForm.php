<?php

namespace App\Filament\Resources\Deudas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DeudaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('persona_id')
                ->label('Persona')
                ->relationship('persona', 'nombre')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('valor')
                ->label('Valor deuda')
                ->numeric()
                ->prefix('$')
                ->required()
                ->minValue(1),
        ]);
    }
}
