<?php

namespace App\Filament\Resources\Contratos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContratosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('persona.nombre')
                    ->label('Conductor')
                    ->searchable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'alquiler' => 'info',
                        'opcion_compra' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('valor_diario')
                    ->label('Valor diario')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fecha_fin')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('documento')
                    ->label('Documento')
                    ->formatStateUsing(fn ($record) => $record->documento ? '📎 Ver' : '-')
                    ->url(fn ($record) => $record->documento ? route('contrato.documento', ['path' => ltrim($record->documento, '/')]) : null, true)
                    ->visible(fn ($record) => $record && (bool) $record->documento),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'finalizado' => 'gray',
                        'cancelado' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('fecha_inicio', 'desc')
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
