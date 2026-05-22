<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Trazabilidad';

    protected static ?string $modelLabel = 'Registro';

    protected static ?string $pluralModelLabel = 'Trazabilidad';

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
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Vehiculo' => 'Vehículos',
                        'Persona' => 'Personas',
                        'Contrato' => 'Contratos',
                        'ControlDiario' => 'Control Diario',
                        'User' => 'Usuarios',
                        default => $state,
                    }),

                TextColumn::make('description')
                    ->label('Evento')
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Entidad')
                    ->formatStateUsing(fn (string $state): string => match (class_basename($state)) {
                        'Vehiculo' => 'Vehículo',
                        'Persona' => 'Persona',
                        'Contrato' => 'Contrato',
                        'ControlDiario' => 'Control Diario',
                        'User' => 'Usuario',
                        default => class_basename($state),
                    }),

                TextColumn::make('subject_id')
                    ->label('ID Entidad'),

                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cambios_formateados')
                    ->label('Cambios')
                    ->getStateUsing(function ($record): string {
                        $changes = $record->attribute_changes;

                        if ($changes->isEmpty()) {
                            return '—';
                        }

                        if ($changes->has('old') && $changes->has('attributes')) {
                            $lines = [];
                            foreach ($changes['old'] as $key => $old) {
                                $new = $changes['attributes'][$key] ?? '(eliminado)';
                                if (is_scalar($old) && is_scalar($new)) {
                                    $lines[] = "{$key}: {$old} → {$new}";
                                }
                            }

                            return implode(', ', $lines) ?: '—';
                        }

                        if ($changes->has('attributes')) {
                            return 'Creado';
                        }

                        if ($changes->has('old')) {
                            return 'Eliminado';
                        }

                        return '—';
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

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }
}
