<?php

namespace Database\Seeders;

use App\Models\Consultorio;
use App\Models\ConsultorioTurno;
use Illuminate\Database\Seeder;

/**
 * Consultorios con sus turnos de atención (datos de desarrollo).
 * Lunes a viernes; el Consultorio 3 trabaja en modo cupos y abre sábado.
 */
class ConsultoriosSeeder extends Seeder
{
    public function run(): void
    {
        $definiciones = [
            ['nombre' => 'Consultorio 1', 'modo_defecto' => 'horario'],
            ['nombre' => 'Consultorio 2', 'modo_defecto' => 'horario'],
            ['nombre' => 'Consultorio 3', 'modo_defecto' => 'cupos'],
        ];

        foreach ($definiciones as $def) {
            $consultorio = Consultorio::firstOrCreate(
                ['nombre' => $def['nombre']],
                ['modo_defecto' => $def['modo_defecto'], 'created_by' => 1],
            );

            $dias = $def['modo_defecto'] === 'cupos' ? [1, 2, 3, 4, 5, 6] : [1, 2, 3, 4, 5];

            foreach ($dias as $dia) {
                // Turno de mañana
                ConsultorioTurno::firstOrCreate(
                    [
                        'consultorio_id' => $consultorio->id,
                        'dia_semana'     => $dia,
                        'hora_inicio'    => '08:00',
                        'hora_fin'       => '12:00',
                    ],
                    $def['modo_defecto'] === 'cupos'
                        ? ['modo' => 'cupos', 'cupos_por_hora' => 4, 'activo' => true]
                        : ['modo' => 'horario', 'slot_minutos' => 30, 'activo' => true],
                );

                // Turno de tarde (no sábado)
                if ($dia <= 5) {
                    ConsultorioTurno::firstOrCreate(
                        [
                            'consultorio_id' => $consultorio->id,
                            'dia_semana'     => $dia,
                            'hora_inicio'    => '13:00',
                            'hora_fin'       => '17:00',
                        ],
                        $def['modo_defecto'] === 'cupos'
                            ? ['modo' => 'cupos', 'cupos_por_hora' => 4, 'activo' => true]
                            : ['modo' => 'horario', 'slot_minutos' => 30, 'activo' => true],
                    );
                }
            }
        }
    }
}
