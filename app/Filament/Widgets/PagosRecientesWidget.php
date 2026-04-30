<?php

namespace App\Filament\Widgets;

use App\Models\ControlDiario;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PagosRecientesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Últimos ajustes del control semanal';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ControlDiario::query()
                    ->with(['vehiculo.persona'])
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo'),

                TextColumn::make('vehiculo.persona.nombre')
                    ->label('Conductor')
                    ->placeholder('Sin conductor'),

                TextColumn::make('valor_generado')
                    ->label('Ingreso')
                    ->money('COP'),

                TextColumn::make('gasto')
                    ->label('Gasto')
                    ->money('COP'),

                TextColumn::make('trabajo')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Trabajó' : 'No trabajó')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since(),

                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(40)
                    ->placeholder('-'),
            ]);
    }
}
