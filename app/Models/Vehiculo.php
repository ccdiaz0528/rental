<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'administrador_vehiculo',
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
            if (auth()->check() && ! auth()->user()->hasRole('admin')) {
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
        return ($this->contratos_count ?? $this->contratos()->count()) === 0
            && ($this->control_diarios_count ?? $this->controlDiarios()->count()) === 0;
    }

    public function deletionBlockers(): string
    {
        $blockers = [];

        if (($this->contratos_count ?? $this->contratos()->count()) > 0) {
            $blockers[] = 'contratos';
        }

        if (($this->control_diarios_count ?? $this->controlDiarios()->count()) > 0) {
            $blockers[] = 'controles semanales';
        }

        return implode(', ', $blockers);
    }
}
