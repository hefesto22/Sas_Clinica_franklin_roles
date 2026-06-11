<?php

namespace Database\Factories;

use App\Models\Consultorio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultorioFactory extends Factory
{
    protected $model = Consultorio::class;

    public function definition(): array
    {
        return [
            'nombre'       => 'Consultorio ' . fake()->unique()->numberBetween(1, 999),
            'modo_defecto' => 'horario',
            'created_by'   => User::factory(),
        ];
    }

    public function modoCupos(): static
    {
        return $this->state(fn () => ['modo_defecto' => 'cupos']);
    }
}
