<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Concerns\HasUserScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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

            if ($vehiculo->isDirty(['persona_id', 'cuota_diaria', 'administracion'])) {
                $now = now();

                $earliest = $vehiculo->vehiculoHistorial()
                    ->orderBy('fecha_inicio')
                    ->first();

                if ($earliest && $earliest->fecha_inicio->startOfDay()->gt($vehiculo->created_at->startOfDay())) {
                    $fechaInicio = $vehiculo->contratos()->min('fecha_inicio');

                    $vehiculo->vehiculoHistorial()->create([
                        'persona_id' => $vehiculo->getOriginal('persona_id'),
                        'cuota_diaria' => $vehiculo->getOriginal('cuota_diaria'),
                        'administracion' => $vehiculo->getOriginal('administracion') ?? 0,
                        'fecha_inicio' => $fechaInicio
                            ? Carbon::parse($fechaInicio)->startOfDay()
                            : $vehiculo->created_at->startOfDay(),
                    ]);
                }

                $vehiculo->vehiculoHistorial()
                    ->whereNull('fecha_fin')
                    ->update(['fecha_fin' => $now]);

                $vehiculo->vehiculoHistorial()->create([
                    'persona_id' => $vehiculo->persona_id,
                    'cuota_diaria' => $vehiculo->cuota_diaria,
                    'administracion' => $vehiculo->administracion ?? 0,
                    'fecha_inicio' => $now,
                ]);
            }
        });

        static::created(function (Vehiculo $vehiculo) {
            $vehiculo->vehiculoHistorial()->create([
                'persona_id' => $vehiculo->persona_id,
                'cuota_diaria' => $vehiculo->cuota_diaria,
                'administracion' => $vehiculo->administracion ?? 0,
                'fecha_inicio' => $vehiculo->created_at,
            ]);
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

    public function vehiculoHistorial(): HasMany
    {
        return $this->hasMany(VehiculoHistorial::class);
    }

    public function historialEnFecha(Carbon $fecha): ?VehiculoHistorial
    {
        $fechaStart = $fecha->copy()->startOfDay();

        if ($this->relationLoaded('vehiculoHistorial')) {
            return $this->vehiculoHistorial
                ->filter(fn ($h) => $h->fecha_inicio->startOfDay()->lte($fechaStart) && ($h->fecha_fin === null || $h->fecha_fin->startOfDay()->gt($fechaStart)))
                ->sortByDesc('fecha_inicio')
                ->first();
        }

        return $this->vehiculoHistorial()
            ->whereDate('fecha_inicio', '<=', $fechaStart)
            ->where(function ($q) use ($fechaStart) {
                $q->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>', $fechaStart);
            })
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    public function cuotaDiariaEn(Carbon $fecha): float
    {
        return (float) ($this->historialEnFecha($fecha)?->cuota_diaria ?? $this->cuota_diaria);
    }

    public function administracionEn(Carbon $fecha): float
    {
        return (float) ($this->historialEnFecha($fecha)?->administracion ?? $this->administracion ?? 0);
    }

    public function personaNombreEn(Carbon $fecha): ?string
    {
        $historial = $this->historialEnFecha($fecha);

        if ($historial) {
            if ($historial->persona_id) {
                if ($historial->relationLoaded('persona') && $historial->persona) {
                    return $historial->persona->nombre;
                }

                return $historial->persona()->value('nombre');
            }

            return null;
        }

        return $this->persona?->nombre;
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
