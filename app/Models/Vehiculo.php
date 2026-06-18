<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id',
    'administrador_vehiculo',
    'placa',
    'marca',
    'modelo',
    'anio',
    'color',
    'persona_id',
    'cuota_diaria',
    'administracion',
    'estado',
    'fecha_inactivacion',
    'restored_at',
    'fecha_eliminacion',
    'observaciones',
    'fecha_vencimiento_soat',
    'fecha_vencimiento_tecnomecanico',
])]
class Vehiculo extends Model
{
    use BelongsToUser;
    use HasUserScope;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'fecha_vencimiento_soat' => 'date',
            'fecha_vencimiento_tecnomecanico' => 'date',
            'fecha_inactivacion' => 'datetime',
            'restored_at' => 'datetime',
            'fecha_eliminacion' => 'datetime',
            'administracion' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Vehiculo $vehiculo) {
            if ($vehiculo->isDirty('estado')) {
                if ($vehiculo->estado === 'inactivo') {
                    $vehiculo->fecha_inactivacion = now();
                } elseif ($vehiculo->getOriginal('estado') === 'inactivo') {
                    $vehiculo->fecha_inactivacion = null;
                }
            }
        });

        static::deleting(function (Vehiculo $vehiculo) {
            if (! $vehiculo->fecha_eliminacion) {
                $vehiculo->fecha_eliminacion = now();
                $vehiculo->saveQuietly();
            }
        });

        static::restored(function (Vehiculo $vehiculo) {
            $vehiculo->restored_at = now();
            $vehiculo->saveQuietly();
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
        return ! $this->trashed();
    }

    public function deletionBlockers(): string
    {
        if ($this->trashed()) {
            return 'ya está eliminado';
        }

        return '';
    }

    public function getEffectiveStartDate(): Carbon
    {
        if ($this->relationLoaded('contratos')) {
            $fecha = $this->contratos->min('fecha_inicio');
        } else {
            $fecha = $this->contratos()->min('fecha_inicio');
        }

        return $fecha ? Carbon::parse($fecha)->startOfDay() : $this->created_at->startOfDay();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Vehiculo')
            ->setDescriptionForEvent(fn (string $eventName) => 'Vehículo '.match ($eventName) {
                'created' => 'creado',
                'updated' => 'actualizado',
                'deleted' => 'eliminado',
                default => $eventName,
            });
    }
}
