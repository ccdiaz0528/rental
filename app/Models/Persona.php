<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id',
    'nombre',
    'cedula',
    'telefono',
    'direccion',
    'tipo',
    'estado',
    'observaciones',
])]
class Persona extends Model
{
    use BelongsToUser;
    use HasUserScope;
    use LogsActivity;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    public function canBeDeleted(): bool
    {
        return ($this->contratos_activos_count ?? $this->contratos()->where('estado', 'activo')->count()) === 0;
    }

    public function deletionBlockers(): string
    {
        if (($this->contratos_activos_count ?? $this->contratos()->where('estado', 'activo')->count()) > 0) {
            return 'contratos activos';
        }

        return '';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Persona')
            ->setDescriptionForEvent(fn (string $eventName) => 'Persona '.match ($eventName) {
                'created' => 'creado',
                'updated' => 'actualizado',
                'deleted' => 'eliminado',
                default => $eventName,
            });
    }
}
