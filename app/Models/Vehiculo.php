<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'anio',
        'color',
        'cuota_diaria',
        'estado',
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
