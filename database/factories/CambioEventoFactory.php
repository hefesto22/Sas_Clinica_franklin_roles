<?php

namespace Database\Factories;

use App\Models\CambioEvento;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CambioEventoFactory extends Factory
{
    protected $model = CambioEvento::class;

    public function definition(): array
    {
        return [
            'evento_id_origen'  => Event::factory(),
            'evento_id_destino' => Event::factory(),
            'created_by'        => User::factory(),
            'approved_by'       => null,
            'estado'            => 'pendiente',
        ];
    }

    public function aceptado(): static
    {
        return $this->state(fn () => [
            'estado'      => 'aceptado',
            'approved_by' => User::factory(),
            'aprobado_en' => now(),
        ]);
    }

    public function rechazado(): static
    {
        return $this->state(fn () => [
            'estado'       => 'rechazado',
            'approved_by'  => User::factory(),
            'rechazado_en' => now(),
        ]);
    }
}
