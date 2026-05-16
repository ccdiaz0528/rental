<?php

namespace Database\Factories;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonaFactory extends Factory
{
    protected $model = Persona::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre' => fake()->name(),
            'cedula' => fake()->unique()->numerify('##########'),
            'telefono' => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'tipo' => fake()->randomElement(['conductor', 'propietario', 'otro']),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    public function conductor(): static
    {
        return $this->state(fn (array $attributes) => ['tipo' => 'conductor']);
    }
}
