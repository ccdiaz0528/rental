<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Persona - Representa clientes y conductores del sistema.
 * 
 * Este modelo agrupa la información de las personas que pueden ser:
 * - Clientes: Personas que rentan vehículos
 * - Conductores: Personas que conducen los vehículos de la flota
 * 
 * La distinción se realiza mediante el campo 'tipo' (cliente/conductor).
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $cedula
 * @property string|null $telefono
 * @property string|null $direccion
 * @property string|null $tipo - 'cliente' o 'conductor'
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Persona extends Model
{
    /**
     * Atributos que pueden ser asignados masivamente.
     * Se incluyen todos los campos editables desde el formulario.
     */
    protected $fillable = [
        'nombre',
        'cedula',
        'telefono',
        'direccion',
        'tipo',
        'observaciones',
    ];

    /**
     * Relación: Una persona puede tener muchos contratos.
     * Un contrato representa el acuerdo de alquiler entre cliente y empresa.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    /**
     * Relación: Una persona (conductor) puede tener muchos vehículos asignados.
     * Un conductor puede manejar varios vehículos a lo largo del tiempo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }
}
