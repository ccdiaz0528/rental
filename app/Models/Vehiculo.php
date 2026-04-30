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
        'persona_id',
        'cuota_diaria',
        'estado',
        'observaciones',
        'fecha_vencimiento_soat',
        'fecha_vencimiento_tecnomecanico',
    ];

    protected $casts = [
        'fecha_vencimiento_soat' => 'date',
        'fecha_vencimiento_tecnomecanico' => 'date',
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function controlDiarios()
    {
        return $this->hasMany(ControlDiario::class);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->contratos()->exists()
            && ! $this->controlDiarios()->exists();
    }

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
