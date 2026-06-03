<?php

namespace App\Filament\Resources\Deudas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeudasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona.nombre')
                    ->label('Persona')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
