<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\ClienteActividad;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteActividadFactory extends Factory
{
    protected $model = ClienteActividad::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'fecha'      => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'actividad'  => fake()->sentence(4),
            'tipo'       => fake()->randomElement(['general', 'ortodoncia']),
            'pago'       => fake()->randomFloat(2, 100, 3000),
        ];
    }
}
