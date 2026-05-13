<?php

namespace Tests\Unit;

use App\Models\Configuracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_when_key_missing(): void
    {
        $value = Configuracion::get('missing_key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_set_and_get(): void
    {
        Configuracion::set('test_key', 'test_value');
        $value = Configuracion::get('test_key');
        $this->assertEquals('test_value', $value);
    }

    public function test_set_overwrites_existing(): void
    {
        Configuracion::set('overwrite_key', 'first');
        Configuracion::set('overwrite_key', 'second');
        $value = Configuracion::get('overwrite_key');
        $this->assertEquals('second', $value);
    }

    public function test_administracion_semanal_default_zero(): void
    {
        Configuracion::set('administracion_semanal', '0');
        $value = Configuracion::get('administracion_semanal', '0');
        $this->assertEquals('0', $value);
    }

    public function test_administracion_semanal_persists(): void
    {
        Configuracion::set('administracion_semanal', '50000');
        $value = Configuracion::get('administracion_semanal', '0');
        $this->assertEquals('50000', $value);

        $dbValue = Configuracion::where('clave', 'administracion_semanal')->first()->valor;
        $this->assertEquals('50000', $dbValue);
    }

    public function test_multiple_keys_independent(): void
    {
        Configuracion::set('key_a', 'value_a');
        Configuracion::set('key_b', 'value_b');

        $this->assertEquals('value_a', Configuracion::get('key_a'));
        $this->assertEquals('value_b', Configuracion::get('key_b'));
        $this->assertEquals('default', Configuracion::get('key_c', 'default'));
    }
}
