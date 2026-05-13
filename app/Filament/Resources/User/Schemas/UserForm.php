<?php

namespace App\Filament\Resources\User\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Correo electrónico')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->label('Contraseña')
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                ->minLength(8)
                ->dehydrated(fn ($state) => filled($state)),

            Select::make('roles')
                ->label('Rol')
                ->relationship('roles', 'name')
                ->preload()
                ->required(),
        ]);
    }
}
