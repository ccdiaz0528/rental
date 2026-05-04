<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Contrato - Representa los contratos de alquiler de vehículos.
 * 
 * Este modelo gestiona la información de los acuerdos entre la empresa de租赁
 * y los clientes. Cada contrato define:
 * - El vehículo involucrado
 * - El cliente (persona que renting)
 * - Período del contrato (fecha inicio y fin)
 * - Valor diario del alquiler
 * - Estado del contrato (activo, finalizado, cancelado)
 *
 * @property int $id
 * @property int $vehiculo_id - Vehículo alquilado
 * @property int $persona_id - Cliente que renting el vehículo
 * @property string|null $tipo - Tipo de contrato (opcional)
 * @property \Carbon\Carbon $fecha_inicio - Fecha de inicio del contrato
 * @property \Carbon\Carbon|null $fecha_fin - Fecha de finalización del contrato
 * @property float $valor_diario - Valor diario del alquiler
 * @property string $estado - 'activo', 'finalizado', 'cancelado'
 * @property string|null $observaciones
 * @property string|null $documento - Ruta al documento del contrato
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Contrato extends Model
{
    /**
     * Atributos que pueden ser asignados masivamente.
     */
    protected $fillable = [
        'vehiculo_id',
        'persona_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'valor_diario',
        'estado',
        'observaciones',
        'documento',
    ];

    /**
     * Conversión de tipos para atributos de fecha.
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación: Un contrato pertenece a un vehículo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * Relación: Un contrato pertenece a una persona (cliente).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
