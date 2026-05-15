<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use App\Models\ControlDiario;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PagosRecientesWidget extends BaseWidget
{
    use HasUserContext;

    protected static ?int $sort = 6;

    protected static ?string $heading = 'Últimos movimientos';

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
                    ->searchable()
                    ->grow(false),

                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->searchable()
                    ->grow(false),

                TextColumn::make('vehiculo.persona.nombre')
                    ->label('Conductor')
                    ->placeholder('Sin conductor'),

                BadgeColumn::make('trabajo')
                    ->label('Estado')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Trabajó' : 'No trabajó')
                    ->grow(false),

                TextColumn::make('valor_generado')
                    ->label('Ingreso')
                    ->money('COP', locale: 'es_CO', decimalPlaces: 0)
                    ->alignEnd()
                    ->grow(false),

                TextColumn::make('gasto')
                    ->label('Gasto')
                    ->money('COP', locale: 'es_CO', decimalPlaces: 0)
                    ->alignEnd()
                    ->color('danger')
                    ->grow(false),

                TextColumn::make('neto')
                    ->label('Neto')
                    ->state(fn ($record) => $record->valor_generado - $record->gasto)
                    ->money('COP', locale: 'es_CO', decimalPlaces: 0)
                    ->alignEnd()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->grow(false),

                BadgeColumn::make('categoria_gasto')
                    ->label('Categoría')
                    ->colors([
                        'danger' => 'daño',
                        'info' => 'mantenimiento',
                        'warning' => 'multa',
                        'gray' => 'otro',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'daño' => '🛠️ Daño',
                        'mantenimiento' => '🔧 Mant.',
                        'multa' => '🚫 Multa',
                        'otro' => '📋 Otro',
                        default => '—',
                    })
                    ->grow(false),
            ])
            ->filters([
                SelectFilter::make('categoria_gasto')
                    ->label('Categoría')
                    ->options([
                        'daño' => 'Daños',
                        'mantenimiento' => 'Mantenimientos',
                        'multa' => 'Multas',
                        'otro' => 'Otros',
                    ]),
            ])
            ->paginated(false)
            ->striped()
            ->searchable();
    }
}
