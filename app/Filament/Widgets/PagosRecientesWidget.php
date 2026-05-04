<?php

namespace App\Filament\Widgets;

use App\Models\ControlDiario;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Widget de tabla - Muestra los últimos ajustes del control semanal.
 * 
 * Este widget muestra una tabla con los últimos 10 registros modificados
 * del control diario, permitiendo al usuario ver rápidamente:
 * - Qué vehículo se modificó
 * - Qué conductor está asignado
 * - El valor generado y gastos registrados
 * - Si el vehículo trabajó o no
 * - Observaciones adicionales
 * 
 * Los registros se ordenan por fecha de actualización (más recientes primero).
 */
class PagosRecientesWidget extends BaseWidget
{
    /**
     * Orden de aparición en el escritorio (2 = después de StatsOverview).
     * @var int|null
     */
    protected static ?int $sort = 2;

    /**
     * Título del widget.
     * @var string|null
     */
    protected static ?string $heading = 'Últimos ajustes del control semanal';

    /**
     * Ocupa el ancho completo del contenedor.
     * @var int|string|array
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Define la estructura de la tabla: consulta, columnas y formato.
     * 
     * La consulta obtiene los 10 registros más recientes ordenados por
     * updated_at (última modificación), con las relaciones de vehículo y persona.
     *
     * @param Table $table - Instancia de la tabla de Filament
     * @return Table Configuración de la tabla
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Consulta: últimos 10 registros de control diario
                ControlDiario::query()
                    ->with(['vehiculo.persona']) // Carga relaciones para mostrar nombres
                    ->latest('updated_at')        // Ordena por última modificación
                    ->limit(10)                  // Limita a 10 registros
            )
            ->columns([
                // Columna: Fecha del registro
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                // Columna: Placa del vehículo
                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo'),

                // Columna: Nombre del conductor (relación anidada)
                TextColumn::make('vehiculo.persona.nombre')
                    ->label('Conductor')
                    ->placeholder('Sin conductor'),

                // Columna: Valor generado (ingreso del día)
                TextColumn::make('valor_generado')
                    ->label('Ingreso')
                    ->money('COP'),

                // Columna: Gasto del día
                TextColumn::make('gasto')
                    ->label('Gasto')
                    ->money('COP'),

                // Columna: Estado (trabajó o no trabajó)
                TextColumn::make('trabajo')
                    ->label('Estado')
                    ->badge() // Muestra como insignia
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Trabajó' : 'No trabajó')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),

                // Columna: Cuándo se actualizó (hace cuánto tiempo)
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since(),

                // Columna: Observaciones (limitado a 40 caracteres)
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(40)
                    ->placeholder('-'),
            ]);
    }
}
