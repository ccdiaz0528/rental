<?php

namespace Database\Factories;

use App\Models\Contrato;
use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContratoFactory extends Factory
{
    protected $model = Contrato::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehiculo_id' => Vehiculo::factory(),
            'persona_id' => Persona::factory(),
            'tipo' => fake()->randomElement(['alquiler', 'opcion_compra']),
            'fecha_inicio' => fake()->dateTimeBetween('-1 year', 'now'),
            'fecha_fin' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'valor_diario' => fake()->randomFloat(2, 50000, 200000),
            'estado' => 'activo',
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => 'activo']);
    }
}
