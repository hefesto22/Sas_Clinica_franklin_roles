<?php

namespace Database\Factories;

use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionDetalleFactory extends Factory
{
    protected $model = EvaluacionDetalle::class;

    public function definition(): array
    {
        // Notación FDI: cuadrantes 1-4, piezas 1-8
        $pieza = fake()->numberBetween(1, 4) . fake()->numberBetween(1, 8);

        return [
            'evaluacion_id' => Evaluacion::factory(),
            'pieza'         => (string) $pieza,
            'diagnostico'   => fake()->sentence(3),
            'hecho'         => false,
        ];
    }

    public function hecho(): static
    {
        return $this->state(fn () => ['hecho' => true]);
    }
}
