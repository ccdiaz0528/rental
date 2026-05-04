<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor'];

    public static function get(string $clave, mixed $default = null): mixed
    {
        $config = static::where('clave', $clave)->first();
        return $config?->valor ?? $default;
    }

    public static function set(string $clave, mixed $value): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $value]
        );
    }
}