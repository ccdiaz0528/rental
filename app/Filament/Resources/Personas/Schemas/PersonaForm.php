<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('cedula'),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('direccion'),
                Select::make('tipo')
                    ->options(['conductor' => 'Conductor', 'propietario' => 'Propietario', 'otro' => 'Otro'])
                    ->default('conductor')
                    ->required(),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ]);
    }
}
