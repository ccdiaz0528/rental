<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlDiario extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'fecha',
        'trabajo',
        'valor_generado',
        'gasto',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'trabajo' => 'boolean',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
