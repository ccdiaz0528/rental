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

    public function pagosDiarios()
    {
        return $this->hasMany(PagoDiario::class);
    }

    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }
}
