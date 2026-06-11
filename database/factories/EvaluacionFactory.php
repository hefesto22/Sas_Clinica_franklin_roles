<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Evaluacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionFactory extends Factory
{
    protected $model = Evaluacion::class;

    public function definition(): array
    {
        return [
            'cliente_id'           => Cliente::factory(),
            'fecha'                => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'limpieza_periodontal' => fake()->randomElement(['Sí', 'No', null]),
            'fluor'                => fake()->randomElement(['Sí', 'No', null]),
            'observaciones'        => fake()->sentence(),
            'user_id'              => User::factory(),
        ];
    }
}
