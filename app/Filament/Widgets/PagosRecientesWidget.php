<?php

namespace App\Filament\Widgets;

use App\Models\PagoDiario;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PagosRecientesWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Últimos pagos registrados';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PagoDiario::query()
                    ->with(['vehiculo', 'persona'])
                    ->latest('fecha')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo'),

                TextColumn::make('persona.nombre')
                    ->label('Conductor'),

                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('COP'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pagado'    => 'success',
                        'pendiente' => 'warning',
                        'debe'      => 'danger',
                        default     => 'gray',
                    }),
            ]);
    }
}
