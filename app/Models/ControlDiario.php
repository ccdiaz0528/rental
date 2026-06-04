<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id',
    'vehiculo_id',
    'fecha',
    'trabajo',
    'valor_generado',
    'gasto',
    'administracion',
    'categoria_gasto',
    'observaciones',
])]
class ControlDiario extends Model
{
    use BelongsToUser;
    use HasUserScope;
    use LogsActivity;

    public const CATEGORIA_DAÑO = 'daño';

    public const CATEGORIA_MANTENIMIENTO = 'mantenimiento';

    public const CATEGORIA_MULTA = 'multa';

    public const CATEGORIA_OTRO = 'otro';

    public const CATEGORIAS = [
        self::CATEGORIA_DAÑO,
        self::CATEGORIA_MANTENIMIENTO,
        self::CATEGORIA_MULTA,
        self::CATEGORIA_OTRO,
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'trabajo' => 'boolean',
            'administracion' => 'decimal:2',
            'categoria_gasto' => 'string',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('ControlDiario')
            ->setDescriptionForEvent(fn (string $eventName) => 'Control diario '.match ($eventName) {
                'created' => 'creado',
                'updated' => 'actualizado',
                'deleted' => 'eliminado',
                default => $eventName,
            });
    }
}
