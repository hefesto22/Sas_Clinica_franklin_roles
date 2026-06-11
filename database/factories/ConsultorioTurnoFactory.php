<?php

namespace Database\Factories;

use App\Models\Consultorio;
use App\Models\ConsultorioTurno;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultorioTurnoFactory extends Factory
{
    protected $model = ConsultorioTurno::class;

    public function definition(): array
    {
        return [
            'consultorio_id' => Consultorio::factory(),
            'dia_semana'     => fake()->numberBetween(1, 5), // Lunes..Viernes
            'hora_inicio'    => '08:00',
            'hora_fin'       => '12:00',
            'modo'           => null, // hereda modo_defecto del consultorio
            'slot_minutos'   => 30,
            'cupos_por_hora' => null,
            'activo'         => true,
        ];
    }

    public function modoHorario(int $slotMinutos = 30): static
    {
        return $this->state(fn () => [
            'modo'           => 'horario',
            'slot_minutos'   => $slotMinutos,
            'cupos_por_hora' => null,
        ]);
    }

    public function modoCupos(int $cuposPorHora = 4): static
    {
        return $this->state(fn () => [
            'modo'           => 'cupos',
            'slot_minutos'   => null,
            'cupos_por_hora' => $cuposPorHora,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
