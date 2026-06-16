<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\ClienteNota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteNotaFactory extends Factory
{
    protected $model = ClienteNota::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'contenido'  => fake()->sentence(),
            'leida'      => false,
            'created_by' => User::factory(),
        ];
    }

    public function leida(): static
    {
        return $this->state(fn () => ['leida' => true]);
    }

    /** Nota ya resuelta (tarea hecha). */
    public function hecha(): static
    {
        return $this->state(fn () => ['hecha_en' => now()]);
    }
}
