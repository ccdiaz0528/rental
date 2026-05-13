<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor'];

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
