<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Persona extends Model
{
    use BelongsToUser;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'nombre',
        'cedula',
        'telefono',
        'direccion',
        'tipo',
        'estado',
        'observaciones',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check() && ! auth()->user()->hasRole('admin')) {
                $builder->where('user_id', auth()->id());
            }
        });
    }

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
            ->setDescriptionForEvent(fn (string $eventName) => "Persona {$eventName}");
    }
}
