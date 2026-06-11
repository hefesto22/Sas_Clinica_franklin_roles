<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\ClienteImagen;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteImagenFactory extends Factory
{
    protected $model = ClienteImagen::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'path'       => 'clientes/' . fake()->uuid() . '.jpg',
        ];
    }
}
