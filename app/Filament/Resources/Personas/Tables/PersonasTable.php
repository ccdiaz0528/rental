<?php

namespace App\Filament\Resources\Personas\Tables;

use App\Models\Persona;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PersonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('Sin teléfono'),

                TextColumn::make('direccion')
                    ->label('Dirección')
                    ->placeholder('Sin dirección'),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'conductor' => 'success',
                        'propietario' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'inactivo' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Persona $record): bool => ! $record->canBeDeleted())
                    ->tooltip(fn (Persona $record): ?string => $record->canBeDeleted()
                        ? null
                        : 'No se puede eliminar porque tiene '.$record->deletionBlockers().'.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
