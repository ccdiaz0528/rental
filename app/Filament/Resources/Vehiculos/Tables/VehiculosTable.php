<?php

namespace App\Filament\Resources\Vehiculos\Tables;

use App\Models\Vehiculo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiculosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('placa')
                    ->label('Placa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable(),

                TextColumn::make('modelo')
                    ->label('Modelo'),

                TextColumn::make('persona.nombre')
                    ->label('Conductor')
                    ->placeholder('Sin conductor')
                    ->searchable(),

                TextColumn::make('anio')
                    ->label('Año'),

                TextColumn::make('fecha_vencimiento_soat')
                    ->label('SOAT')
                    ->date('d/m/Y')
                    ->badge()
                    ->color(fn ($record) => $record->fecha_vencimiento_soat
                        ? (now()->gt($record->fecha_vencimiento_soat) ? 'danger' : (now()->diffInDays($record->fecha_vencimiento_soat) <= 30 ? 'warning' : 'success'))
                        : 'gray'),

                TextColumn::make('fecha_vencimiento_tecnomecanico')
                    ->label('Tecnomecánica')
                    ->date('d/m/Y')
                    ->badge()
                    ->color(fn ($record) => $record->fecha_vencimiento_tecnomecanico
                        ? (now()->gt($record->fecha_vencimiento_tecnomecanico) ? 'danger' : (now()->diffInDays($record->fecha_vencimiento_tecnomecanico) <= 30 ? 'warning' : 'success'))
                        : 'gray'),

                TextColumn::make('cuota_diaria')
                    ->label('Cuota diaria')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'inactivo' => 'danger',
                        'mantenimiento' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Vehiculo $record): bool => ! $record->canBeDeleted())
                    ->tooltip(fn (Vehiculo $record): ?string => $record->canBeDeleted()
                        ? null
                        : 'No se puede eliminar porque tiene '.$record->deletionBlockers().'.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(false),
                ]),
            ]);
    }
}
