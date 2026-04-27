<?php

namespace App\Filament\Resources\PagoDiarios\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagoDiariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('persona.nombre')
                    ->label('Conductor')
                    ->searchable(),

                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pagado'    => 'success',
                        'pendiente' => 'warning',
                        'debe'      => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('fecha', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
