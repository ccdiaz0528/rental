<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlDiario extends Model
{
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

    protected $fillable = [
        'user_id',
        'vehiculo_id',
        'fecha',
        'trabajo',
        'valor_generado',
        'gasto',
        'categoria_gasto',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'trabajo' => 'boolean',
        'categoria_gasto' => 'string',
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
}
