<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use App\Models\User;
use App\Models\Vehiculo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('placa')
                ->label('Placa')
                ->required()
                ->unique(ignoreRecord: true)
                ->validationMessages([
                    'unique' => 'Ya existe un vehículo con esta placa (puede estar eliminado).',
                ])
                ->maxLength(10),

            Select::make('administrador_vehiculo')
                ->label('Administrador vehículo')
                ->options(function (?Vehiculo $record) {
                    if (auth()->user()?->hasRole('admin')) {
                        return User::pluck('name', 'name');
                    }

                    // Un usuario normal no debe ver la lista de usuarios del sistema.
                    return collect([auth()->user()?->name, $record?->administrador_vehiculo])
                        ->filter()
                        ->mapWithKeys(fn (string $name) => [$name => $name]);
                })
                ->searchable()
                ->nullable()
                ->default(fn () => auth()->user()?->name),

            TextInput::make('marca')
                ->label('Marca')
                ->maxLength(50),

            TextInput::make('modelo')
                ->label('Modelo')
                ->maxLength(50),

            TextInput::make('anio')
                ->label('Año')
                ->numeric()
                ->minValue(1990)
                ->maxValue(2030),

            TextInput::make('color')
                ->label('Color')
                ->maxLength(30),

            DatePicker::make('fecha_vencimiento_soat')
                ->label('Vencimiento SOAT')
                ->nullable(),

            DatePicker::make('fecha_vencimiento_tecnomecanico')
                ->label('Vencimiento Tecnomecánica')
                ->nullable(),

            Select::make('persona_id')
                ->label('Conductor')
                ->relationship('persona', 'nombre', fn ($query) => $query->where('estado', 'activo'))
                ->searchable()
                ->preload()
                ->nullable(),

            TextInput::make('cuota_diaria')
                ->label('Cuota diaria')
                ->numeric()
                ->prefix('$')
                ->required()
                ->default(0),

            TextInput::make('administracion')
                ->label('Administración')
                ->numeric()
                ->prefix('$')
                ->required()
                ->default(0),

            Select::make('estado')
                ->label('Estado')
                ->options([
                    'activo' => 'Activo',
                    'inactivo' => 'Inactivo',
                    'mantenimiento' => 'En mantenimiento',
                ])
                ->required()
                ->default('activo'),

            DateTimePicker::make('fecha_inactivacion')
                ->label('Inactivado el')
                ->disabled()
                ->hidden(fn ($record) => $record === null || $record->estado !== 'inactivo'),

            Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
