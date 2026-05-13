<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    protected $fillable = [
        'user_id',
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

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function controlDiarios(): HasMany
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
