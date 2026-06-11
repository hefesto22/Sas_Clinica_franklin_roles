<?php

namespace Database\Factories;

use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EspecialidadFactory extends Factory
{
    protected $model = Especialidad::class;

    public function definition(): array
    {
        return [
            'nombre'      => fake()->unique()->randomElement([
                'Ortodoncia', 'Endodoncia', 'Periodoncia', 'Odontopediatría',
                'Cirugía Oral', 'Estética Dental', 'Prostodoncia', 'Implantología',
            ]) . ' ' . fake()->unique()->numberBetween(1, 9999),
            'descripcion' => fake()->sentence(),
            'estado'      => 'activo',
            'created_by'  => User::factory(),
        ];
    }
}
