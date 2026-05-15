<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
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
                ->maxLength(10),

            Select::make('administrador_vehiculo')
                ->label('Administrador vehículo')
                ->options(fn () => User::pluck('name', 'name'))
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
                ->relationship('persona', 'nombre')
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

            Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
