<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $fillable = [
        'nombre',
        'cedula',
        'telefono',
        'direccion',
        'tipo',
        'observaciones',
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }
}
