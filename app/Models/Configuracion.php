<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['clave', 'valor'])]
class Configuracion extends Model
{
    use LogsActivity;

    protected $table = 'configuraciones';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Configuracion')
            ->setDescriptionForEvent(fn (string $eventName) => 'Configuración '.match ($eventName) {
                'created' => 'creado',
                'updated' => 'actualizado',
                'deleted' => 'eliminado',
                default => $eventName,
            });
    }

    public static function get(string $clave, mixed $default = null): mixed
    {
        return Cache::remember("configuracion.{$clave}", now()->addHour(), function () use ($clave, $default) {
            $config = static::where('clave', $clave)->first();

            return $config?->valor ?? $default;
        });
    }

    public static function set(string $clave, mixed $value): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $value]
        );

        Cache::forget("configuracion.{$clave}");
    }
}
