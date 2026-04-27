<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'persona_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'valor_diario',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function pagosDiarios()
    {
        return $this->hasMany(PagoDiario::class);
    }
}
