<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label('Nombre completo')
                ->required()
                ->maxLength(255),

            TextInput::make('cedula')
                ->label('Cédula')
                ->unique(ignoreRecord: true)
                ->maxLength(20),

            TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->maxLength(20),

            TextInput::make('direccion')
                ->label('Dirección')
                ->maxLength(255),

            Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'conductor' => 'Conductor',
                    'propietario' => 'Propietario',
                    'otro' => 'Otro',
                ])
                ->required()
                ->default('conductor'),

            Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
