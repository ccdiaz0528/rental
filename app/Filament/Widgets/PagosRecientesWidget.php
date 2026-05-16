<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Models\ControlDiario;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PagosRecientesWidget extends BaseWidget
{
    use HasUserContext;

    protected static ?int $sort = 6;

    protected static ?string $heading = 'Ultimos movimientos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = $this->applyUserScope(
            ControlDiario::query()->with(['vehiculo.persona'])->latest('updated_at')->limit(10)
        );

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->grow(false),

                TextColumn::make('vehiculo.placa')
                    ->label('Vehiculo')
                    ->sortable()
                    ->grow(false),

                TextColumn::make('vehiculo.persona.nombre')
                    ->label('Conductor')
                    ->placeholder('Sin conductor'),

                TextColumn::make('trabajo')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Trabajo' : 'No trabajo')
                    ->grow(false),

                TextColumn::make('valor_generado')
                    ->label('Ingreso')
                    ->money('COP', locale: 'es_CO', decimalPlaces: 0)
                    ->alignEnd()
                    ->sortable()
                    ->grow(false),

                TextColumn::make('gasto')
                    ->label('Gasto')
                    ->money('COP', locale: 'es_CO', decimalPlaces: 0)
                    ->alignEnd()
                    ->color('danger')
                    ->sortable()
                    ->grow(false),

                TextColumn::make('neto')
                    ->label('Neto')
                    ->state(fn ($record) => $record->valor_generado - $record->gasto)
                    ->money('COP', locale: 'es_CO', decimalPlaces: 0)
                    ->alignEnd()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->grow(false),

                TextColumn::make('categoria_gasto')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'dano' => 'danger',
                        'mantenimiento' => 'info',
                        'multa' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'dano' => 'Dano',
                        'mantenimiento' => 'Mant.',
                        'multa' => 'Multa',
                        'otro' => 'Otro',
                        default => '—',
                    })
                    ->grow(false),
            ])
            ->filters([])
            ->paginated(false)
            ->striped()
            ->searchable();
    }
}
