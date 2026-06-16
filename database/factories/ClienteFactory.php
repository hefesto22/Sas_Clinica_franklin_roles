<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre'           => fake()->name(),
            'dni'              => fake()->unique()->numerify('####-####-#####'),
            'tipo_paciente'    => fake()->randomElement(['general', 'ortodoncia']),
            'telefono'         => fake()->numerify('9###-####'),
            'direccion'        => fake()->streetAddress(),
            'ocupacion'        => fake()->jobTitle(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-70 years', '-5 years')->format('Y-m-d'),
            'motivo_consulta'  => fake()->sentence(),
            'alergias'         => null,
            'estado'           => 'activo',
            'created_by'       => User::factory(),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['estado' => 'inactivo']);
    }
}
