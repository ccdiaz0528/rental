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
    'persona_id',
    'valor',
])]
class Deuda extends Model
{
    use BelongsToUser;
    use HasUserScope;
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Deuda')
            ->setDescriptionForEvent(fn (string $eventName) => 'Deuda '.match ($eventName) {
                'created' => 'creado',
                'updated' => 'actualizado',
                'deleted' => 'eliminado',
                default => $eventName,
            });
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
