<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Configuración - Almacena configuraciones globales del sistema.
 * 
 * Este modelo proporciona un sistema clave-valor para almacenar
 * configuraciones persistentes del sistema, como:
 * - administracion_semanal: Costo de administración semanal
 * - Otros ajustes globales que necesite la aplicación
 * 
 * Utiliza el patrón Singleton a través de los métodos estáticos get/set.
 *
 * @property int $id
 * @property string $clave - Identificador único de la configuración
 * @property string $valor - Valor de la configuración
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Configuracion extends Model
{
    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'configuraciones';

    /**
     * Atributos que pueden ser asignados masivamente.
     */
    protected $fillable = ['clave', 'valor'];

    /**
     * Obtiene el valor de una configuración.
     * 
     * Busca una configuración por su clave y retorna su valor.
     * Si no existe, retorna el valor por defecto proporcionado.
     *
     * @param string $clave - Identificador de la configuración
     * @param mixed $default - Valor por defecto si no existe la clave
     * @return mixed - Valor de la configuración o el valor por defecto
     */
    public static function get(string $clave, mixed $default = null): mixed
    {
        $config = static::where('clave', $clave)->first();
        return $config?->valor ?? $default;
    }

    /**
     * Establece o actualiza el valor de una configuración.
     * 
     * Utiliza updateOrCreate para crear la configuración si no existe
     * o actualizarla si ya existe.
     *
     * @param string $clave - Identificador de la configuración
     * @param mixed $value - Nuevo valor para la configuración
     * @return void
     */
    public static function set(string $clave, mixed $value): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $value]
        );
    }
}