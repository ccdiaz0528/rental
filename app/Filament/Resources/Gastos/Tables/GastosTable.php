<?php

namespace App\Filament\Resources\Gastos\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GastosTable
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

                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mantenimiento' => 'warning',
                        'aceite'        => 'info',
                        'lavado'        => 'info',
                        'tanqueada'     => 'success',
                        'mecanica'      => 'danger',
                        'llantas'       => 'warning',
                        'fotomulta'     => 'danger',
                        'prestamo'      => 'gray',
                        default         => 'gray',
                    }),

                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('persona.nombre')
                    ->label('Responsable')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(40)
                    ->toggleable(),
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
