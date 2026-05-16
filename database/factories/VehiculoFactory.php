<?php

namespace Database\Factories;

use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehiculoFactory extends Factory
{
    protected $model = Vehiculo::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'placa' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'marca' => fake()->randomElement(['Toyota', 'Chevrolet', 'Renault', 'Mazda', 'Nissan', 'Hyundai', 'Kia', 'Suzuki']),
            'modelo' => fake()->randomElement(['2020', '2021', '2022', '2023', '2024']),
            'anio' => fake()->numberBetween(2010, 2024),
            'color' => fake()->safeColorName(),
            'cuota_diaria' => fake()->randomFloat(2, 50000, 200000),
            'administracion' => fake()->randomFloat(2, 0, 30000),
            'estado' => 'activo',
            'persona_id' => Persona::factory(),
            'fecha_vencimiento_soat' => fake()->optional()->dateTimeBetween('-1 year', '+1 year'),
            'fecha_vencimiento_tecnomecanico' => fake()->optional()->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => 'activo']);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => 'inactivo']);
    }
}
