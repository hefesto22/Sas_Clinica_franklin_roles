<?php

namespace Database\Factories;

use App\Models\Especialidad;
use App\Models\ServicioEspecialidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServicioEspecialidadFactory extends Factory
{
    protected $model = ServicioEspecialidad::class;

    public function definition(): array
    {
        return [
            'especialidad_id' => Especialidad::factory(),
            'nombre'          => 'Servicio ' . fake()->unique()->numberBetween(1, 99999),
            'descripcion'     => fake()->sentence(),
            'precio'          => fake()->randomFloat(2, 200, 5000),
            'estado'          => 'activo',
            'created_by'      => User::factory(),
        ];
    }
}
