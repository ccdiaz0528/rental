<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationLabel = 'Trazabilidad';

    protected static ?int $navigationSort = 100;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('log_name')
                    ->label('Módulo')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Evento')
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Entidad')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),

                TextColumn::make('subject_id')
                    ->label('ID Entidad'),

                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('properties')
                    ->label('Cambios')
                    ->formatStateUsing(function ($state): string {
                        if (! $state) {
                            return '—';
                        }
                        $data = is_string($state) ? json_decode($state, true) : $state;
                        if (! isset($data['old'])) {
                            return 'Creado';
                        }
                        $lines = [];
                        foreach ($data['old'] as $key => $old) {
                            $new = $data['attributes'][$key] ?? null;
                            $lines[] = "{$key}: {$old} → {$new}";
                        }

                        return implode("\n", $lines);
                    })
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Módulo')
                    ->options([
                        'Vehiculo' => 'Vehículos',
                        'Persona' => 'Personas',
                        'Contrato' => 'Contratos',
                        'ControlDiario' => 'Control Diario',
                        'User' => 'Usuarios',
                    ]),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                    ]),
            ])
            ->actions([
                //
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }
}
