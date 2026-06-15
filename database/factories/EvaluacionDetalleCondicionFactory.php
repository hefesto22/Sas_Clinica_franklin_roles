<?php

namespace Database\Factories;

use App\Models\EvaluacionDetalle;
use App\Models\EvaluacionDetalleCondicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionDetalleCondicionFactory extends Factory
{
    protected $model = EvaluacionDetalleCondicion::class;

    public function definition(): array
    {
        return [
            'evaluacion_detalle_id' => EvaluacionDetalle::factory(),
            'condicion'             => fake()->randomElement(array_keys(EvaluacionDetalle::CONDICIONES)),
            'nota'                  => fake()->optional()->sentence(4),
            'tratada'               => false,
            'detectada_en'          => now()->toDateString(),
            'tratada_en'            => null,
        ];
    }

    /** Condición ya tratada, con su fecha de tratamiento. */
    public function tratada(): static
    {
        return $this->state(fn () => [
            'tratada'    => true,
            'tratada_en' => now()->toDateString(),
        ]);
    }

    /** Fija una condición concreta del catálogo. */
    public function condicion(string $condicion): static
    {
        return $this->state(fn () => ['condicion' => $condicion]);
    }
}
