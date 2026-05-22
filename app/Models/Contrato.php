<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Contrato extends Model
{
    use BelongsToUser;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'vehiculo_id',
        'persona_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'valor_diario',
        'estado',
        'observaciones',
        'documento',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
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

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Contrato')
            ->setDescriptionForEvent(fn (string $eventName) => 'Contrato '.match ($eventName) {
                'created' => 'creado',
                'updated' => 'actualizado',
                'deleted' => 'eliminado',
                default => $eventName,
            });
    }
}
