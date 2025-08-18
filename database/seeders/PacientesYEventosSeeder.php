<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Event;
use App\Models\Consultorio;
use App\Models\ConsultorioTurno;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PacientesYEventosSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        // ---------- 1) Crear 500 clientes ----------
        $clientes = [];
        for ($i = 0; $i < 500; $i++) {
            $clientes[] = [
                'nombre'                 => $faker->name(),
                'dni'                    => $faker->unique()->numerify('###########'),
                'telefono'               => $faker->numerify('3########'),
                'direccion'              => $faker->address(),
                'ocupacion'              => $faker->randomElement(['Empleado', 'Independiente', 'Estudiante', 'Docente', 'Comerciante']),
                'fecha_nacimiento'       => $faker->date('Y-m-d', '2005-12-31'),
                'contacto_emergencia_nombre'   => $faker->name(),
                'contacto_emergencia_telefono' => $faker->numerify('3########'),
                'motivo_consulta'        => $faker->randomElement(['Control', 'Dolor', 'Limpieza', 'Ortodoncia']),
                'alergias'               => $faker->randomElement([null, 'Penicilina', 'Anestesia', 'Latex']),
                'estado'                 => 'activo',
                'created_by'             => 1,
                'updated_by'             => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ];
        }

        DB::table('clientes')->insert($clientes);

        // Traer los clientes recién creados (ids)
        $clientesIds = Cliente::orderBy('id', 'desc')->take(500)->pluck('id')->reverse()->values();

        // ---------- 2) Preparar disponibilidad de consultorios ----------
        $consultorios = Consultorio::query()->pluck('id');

        if ($consultorios->isEmpty()) {
            $this->command->warn('No hay consultorios. Aborta creación de eventos.');
            return;
        }

        // Cargar todos los turnos activos agrupados por consultorio
        $turnosPorConsultorio = ConsultorioTurno::query()
            ->where('activo', true)
            ->orderBy('consultorio_id')
            ->get()
            ->groupBy('consultorio_id');

        if ($turnosPorConsultorio->isEmpty()) {
            $this->command->warn('No hay turnos activos en consultorio_turnos. Aborta creación de eventos.');
            return;
        }

        // ---------- 3) Asignar 1 cita por cliente respetando la disponibilidad ----------
        $inicio = Carbon::create(2025, 8, 20, 0, 0, 0);

        $eventosCreados = 0;
        $clienteIndex   = 0;

        while ($clienteIndex < $clientesIds->count()) {
            // Avanza día por día y genera slots por consultorio según el día de la semana
            foreach ($consultorios as $consultorioId) {
                $turnos = $turnosPorConsultorio->get($consultorioId);
                if (!$turnos) {
                    continue;
                }

                // día de semana 1..7 (como guardas en tu tabla)
                $dow = (int) $inicio->isoWeekday(); // 1 = Lunes ... 7 = Domingo
                $turnosDelDia = $turnos->where('dia_semana', $dow);

                foreach ($turnosDelDia as $t) {
                    $slots = $this->generarSlotsParaDia($inicio, $t); // array de [start_at, end_at]

                    foreach ($slots as [$startAt, $endAt]) {
                        if ($clienteIndex >= $clientesIds->count()) {
                            break 2; // sal de ambos loops
                        }

                        // Evitar doble booking en mismo consultorio/hora
                        $choque = Event::where('consultorio_id', $consultorioId)
                            ->where(function ($q) use ($startAt, $endAt) {
                                $q->whereBetween('start_at', [$startAt, $endAt])
                                    ->orWhereBetween('end_at', [$startAt, $endAt])
                                    ->orWhere(function ($q2) use ($startAt, $endAt) {
                                        $q2->where('start_at', '<=', $startAt)
                                            ->where('end_at', '>=', $endAt);
                                    });
                            })->exists();

                        if ($choque) {
                            continue;
                        }

                        // Crear el evento
                        $clienteId = $clientesIds[$clienteIndex];
                        $telefono  = Cliente::find($clienteId)?->telefono ?? '00000000';

                        $event = Event::create([
                            'cliente_id'     => $clienteId,
                            'consultorio_id' => $consultorioId,
                            'telefono'       => $telefono,
                            'estado'         => 'Pendiente',
                            'start_at'       => $startAt,
                            'end_at'         => $endAt,
                            'created_by'     => 1,
                            'updated_by'     => 1,
                        ]);

                        // Pivotes: especialidad 1, servicio 1
                        // Relación: asume $event->especialidades() y $event->servicios() existen
                        $event->especialidades()->syncWithoutDetaching([1]);
                        $event->servicios()->syncWithoutDetaching([1]);

                        $eventosCreados++;
                        $clienteIndex++;

                        if ($clienteIndex >= $clientesIds->count()) {
                            break 2;
                        }
                    }
                }
            }

            // siguiente día
            $inicio->addDay();
        }

        $this->command->info("Se crearon {$eventosCreados} eventos para 500 pacientes.");
    }

    /**
     * Genera los slots de un día para un turno dado.
     * Soporta modo 'horario' (usa slot_minutos) y 'cupos' (distribuye cupos_por_hora dentro de cada hora).
     *
     * @param \Carbon\Carbon $fecha
     * @param \App\Models\ConsultorioTurno $t
     * @return array<int, array{0: string, 1: string}>  Lista de [start_at, end_at] en formato Y-m-d H:i:s
     */
    protected function generarSlotsParaDia(Carbon $fecha, ConsultorioTurno $t): array
    {
        $slots = [];
        $inicio = Carbon::parse($fecha->toDateString() . ' ' . $t->hora_inicio);
        $fin    = Carbon::parse($fecha->toDateString() . ' ' . $t->hora_fin);

        // Helpers para alinear a mallas "bonitas"
        $alignToGrid = function (Carbon $c, int $grid) {
            $m = $c->minute;
            $n = (int) ceil($m / $grid) * $grid;
            if ($n >= 60) {
                $c->addHour()->minute(0)->second(0);
            } else {
                $c->minute($n)->second(0);
            }
            return $c;
        };

        if ($t->modo === 'horario') {
            // respeta slot_minutos y alinea a 10 min
            $dur = max((int) $t->slot_minutos, 10);
            $cursor = $alignToGrid($inicio->copy(), 10);

            while ($cursor->lt($fin)) {
                $end = $cursor->copy()->addMinutes($dur);
                if ($end->gt($fin)) break;
                $slots[] = [$cursor->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
                $cursor->addMinutes($dur);
                // asegura que siempre caiga en múltiplos de 10
                $cursor = $alignToGrid($cursor, 10);
            }
        } else {
            // CUPOS POR HORA en malla de 5 min (hasta 12 cupos/hora)
            $cupos = max(min((int) $t->cupos_por_hora, 12), 1); // 1..12
            $grid  = 5;                                        // 00,05,10,...,55
            $step  = max((int) floor((60 / $cupos) / $grid) * $grid, $grid);

            // Recorre hora por hora
            $hora = $inicio->copy();
            while ($hora->lt($fin)) {
                $horaEnd = $hora->copy()->addHour();
                if ($horaEnd->gt($fin)) $horaEnd = $fin->copy();

                // primer punto alineado a malla de 5 min
                $cursor = $alignToGrid($hora->copy(), $grid);

                $creados = 0;
                while ($cursor->lt($horaEnd) && $creados < $cupos) {
                    $end = $cursor->copy()->addMinutes($step);
                    if ($end->gt($horaEnd)) break;

                    $slots[] = [$cursor->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
                    $creados++;

                    $cursor->addMinutes($step);
                    // re-alinea por si step no es múltiplo exacto
                    $cursor = $alignToGrid($cursor, $grid);
                }

                $hora = $hora->copy()->addHour()->minute(0)->second(0);
            }
        }

        return $slots;
    }
}
