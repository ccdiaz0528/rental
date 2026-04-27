<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $fillable = [
        'vehiculo_id',
        'persona_id',
        'fecha',
        'categoria',
        'valor',
        'detalle',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
