<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de Vehículo - Representa la flota de vehículos del sistema de alquiler.
 *
 * Este modelo gestiona la información de cada vehículo en la flota, incluyendo:
 * - Datos técnicos: placa, marca, modelo, año, color
 * - Información de租赁: conductor asignado, cuota diaria, estado
 * - Documentos: SOAT, tecnomecánico (fechas de vencimiento)
 *
 * @property int $id
 * @property string $placa - Identificador único del vehículo
 * @property string|null $marca
 * @property string|null $modelo
 * @property int|null $anio
 * @property string|null $color
 * @property int|null $persona_id - Conductor asignado (relación con Persona)
 * @property float $cuota_diaria - Valor que debe pagar el conductor diariamente
 * @property string $estado - 'activo' o 'inactivo'
 * @property string|null $observaciones
 * @property Carbon|null $fecha_vencimiento_soat
 * @property Carbon|null $fecha_vencimiento_tecnomecanico
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Vehiculo extends Model
{
    /**
     * Atributos que pueden ser asignados masivamente.
     */
    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'anio',
        'color',
        'persona_id',
        'cuota_diaria',
        'estado',
        'observaciones',
        'fecha_vencimiento_soat',
        'fecha_vencimiento_tecnomecanico',
    ];

    /**
     * Conversión de tipos para atributos de fecha.
     * Las fechas de vencimiento de documentos se convierten a objetos Carbon.
     */
    protected $casts = [
        'fecha_vencimiento_soat' => 'date',
        'fecha_vencimiento_tecnomecanico' => 'date',
    ];

    /**
     * Relación: Un vehículo puede tener muchos contratos.
     * Cada contrato representa un período de alquiler del vehículo.
     *
     * @return HasMany
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    /**
     * Relación: Un vehículo pertenece a una persona (conductor).
     * Un conductor puede tener asignado un vehículo a la vez.
     *
     * @return BelongsTo
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Relación: Un vehículo tiene muchos registros de control diario.
     * El control diario registra el trabajo y finanzas de cada día.
     *
     * @return HasMany
     */
    public function controlDiarios()
    {
        return $this->hasMany(ControlDiario::class);
    }

    /**
     * Verifica si el vehículo puede ser eliminado.
     *
     * Un vehículo no puede eliminarse si tiene contratos activos
     * o registros de control semanal asociados.
     *
     * @return bool True si el vehículo puede ser eliminado
     */
    public function canBeDeleted(): bool
    {
        return ! $this->contratos()->exists()
            && ! $this->controlDiarios()->exists();
    }

    /**
     * Retorna los motivos por los que el vehículo no puede ser eliminado.
     *
     * Si el vehículo tiene contratos o controles asociados, devuelve
     * una cadena con los tipos de registros que bloquean la eliminación.
     *
     * @return string Cadena con los bloqueadores separados por comas
     */
    public function deletionBlockers(): string
    {
        $blockers = [];

        if ($this->contratos()->exists()) {
            $blockers[] = 'contratos';
        }

        if ($this->controlDiarios()->exists()) {
            $blockers[] = 'controles semanales';
        }

        return implode(', ', $blockers);
    }
}
