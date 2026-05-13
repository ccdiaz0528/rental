<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Control Diario - Registros del control semanal por día y vehículo.
 *
 * Este modelo almacena los registros de trabajo diario de cada vehículo.
 * Es la base de datos del "Control Semanal" donde se registra:
 * - Si el vehículo trabajó o no ese día
 * - El valor generado ese día (puede diferir de la cuota diaria)
 * - Los gastos del día (mantenimiento, multas, etc.)
 * - Observaciones adicionales
 *
 * Si no hay registro para un vehículo en un día específico, el sistema
 * usa valores por defecto (trabajo=true, valor=cuota_diaria del vehículo).
 *
 * @property int $id
 * @property int $vehiculo_id - Vehículo al que pertenece el registro
 * @property Carbon $fecha - Fecha del registro
 * @property bool $trabajo - true si el vehículo trabajó, false si no
 * @property float|null $valor_generado - Ingreso del día (null usa cuota_diaria)
 * @property float $gasto - Gastos del día
 * @property string|null $observaciones - Notas adicionales
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ControlDiario extends Model
{
    public const CATEGORIA_DAÑO = 'daño';

    public const CATEGORIA_MANTENIMIENTO = 'mantenimiento';

    public const CATEGORIA_MULTA = 'multa';

    public const CATEGORIA_OTRO = 'otro';

    public const CATEGORIAS = [
        self::CATEGORIA_DAÑO,
        self::CATEGORIA_MANTENIMIENTO,
        self::CATEGORIA_MULTA,
        self::CATEGORIA_OTRO,
    ];

    /**
     * Atributos que pueden ser asignados masivamente.
     */
    protected $fillable = [
        'vehiculo_id',
        'fecha',
        'trabajo',
        'valor_generado',
        'gasto',
        'categoria_gasto',
        'observaciones',
    ];

    /**
     * Conversión de tipos:
     * - fecha: Se convierte a objeto Carbon para manejo de fechas
     * - trabajo: Se convierte a booleano
     * - categoria_gasto: Enum de categoría del gasto
     */
    protected $casts = [
        'fecha' => 'date',
        'trabajo' => 'boolean',
        'categoria_gasto' => 'string',
    ];

    /**
     * Relación: Un control diario pertenece a un vehículo.
     *
     * @return BelongsTo
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
