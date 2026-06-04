<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Concerns\HasUserScope;
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
            'administracion' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
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
