<?php

namespace Database\Factories;

use App\Models\ControlDiario;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

class ControlDiarioFactory extends Factory
{
    protected $model = ControlDiario::class;

    public function definition(): array
    {
        $valorGenerado = fake()->randomFloat(2, 50000, 200000);
        $gasto = fake()->optional(0.3)->randomFloat(2, 0, 50000);

        return [
            'user_id' => User::factory(),
            'vehiculo_id' => Vehiculo::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now'),
            'trabajo' => true,
            'valor_generado' => $valorGenerado,
            'gasto' => $gasto ?? 0,
            'categoria_gasto' => $gasto > 0 ? fake()->randomElement(ControlDiario::CATEGORIAS) : null,
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    public function conGasto(): static
    {
        return $this->state(fn (array $attributes) => [
            'gasto' => fake()->randomFloat(2, 10000, 100000),
            'categoria_gasto' => fake()->randomElement(ControlDiario::CATEGORIAS),
        ]);
    }

    public function sinTrabajo(): static
    {
        return $this->state(fn (array $attributes) => [
            'trabajo' => false,
            'valor_generado' => 0,
        ]);
    }
}
