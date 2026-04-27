<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoDiario extends Model
{
    protected $table = 'pagos_diarios';

    protected $fillable = [
        'contrato_id',
        'vehiculo_id',
        'persona_id',
        'fecha',
        'valor',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
