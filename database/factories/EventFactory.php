<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Consultorio;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'))
            ->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        return [
            'cliente_id'     => Cliente::factory(),
            'consultorio_id' => Consultorio::factory(),
            'doctor_id'      => null,
            'telefono'       => fake()->numerify('9###-####'),
            'estado'         => 'Pendiente',
            'start_at'       => $start,
            'end_at'         => $start->copy()->addMinutes(30),
            'created_by'     => User::factory(),
        ];
    }

    /** Cita en una franja exacta. */
    public function enFranja(string $inicio, ?string $fin = null): static
    {
        $start = Carbon::parse($inicio);

        return $this->state(fn () => [
            'start_at' => $start,
            'end_at'   => $fin ? Carbon::parse($fin) : $start->copy()->addMinutes(30),
        ]);
    }

    public function pendiente(): static
    {
        return $this->state(fn () => ['estado' => 'Pendiente']);
    }

    public function confirmado(): static
    {
        return $this->state(fn () => ['estado' => 'Confirmado']);
    }

    public function cancelado(): static
    {
        return $this->state(fn () => ['estado' => 'Cancelado']);
    }

    public function reagendado(): static
    {
        return $this->state(fn () => ['estado' => 'Reagendado']);
    }

    public function sePresento(): static
    {
        return $this->state(fn () => ['estado' => 'Se Presentó']);
    }

    public function conDoctor(?User $doctor = null): static
    {
        return $this->state(fn () => ['doctor_id' => $doctor?->id ?? User::factory()]);
    }
}
