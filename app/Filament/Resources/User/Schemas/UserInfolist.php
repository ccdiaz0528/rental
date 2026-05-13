<?php

namespace App\Filament\Resources\User\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nombre')
                    ->icon('heroicon-o-user'),
                TextEntry::make('email')
                    ->label('Correo electrónico')
                    ->icon('heroicon-o-envelope'),
                TextEntry::make('roles.name')
                    ->label('Rol')
                    ->badge(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}