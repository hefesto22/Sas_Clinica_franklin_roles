<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\ServicioEspecialidad;
use Illuminate\Database\Seeder;

/**
 * Especialidades de la clínica con sus servicios base (datos de desarrollo).
 */
class EspecialidadesSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'Odontología General' => [
                ['nombre' => 'Consulta general', 'precio' => 500],
                ['nombre' => 'Limpieza dental', 'precio' => 800],
                ['nombre' => 'Extracción simple', 'precio' => 900],
            ],
            'Ortodoncia' => [
                ['nombre' => 'Evaluación de ortodoncia', 'precio' => 600],
                ['nombre' => 'Control de brackets', 'precio' => 700],
            ],
            'Endodoncia' => [
                ['nombre' => 'Tratamiento de conducto', 'precio' => 3500],
            ],
            'Odontopediatría' => [
                ['nombre' => 'Consulta pediátrica', 'precio' => 450],
                ['nombre' => 'Aplicación de flúor', 'precio' => 350],
            ],
        ];

        foreach ($catalogo as $especialidadNombre => $servicios) {
            $especialidad = Especialidad::firstOrCreate(
                ['nombre' => $especialidadNombre],
                ['estado' => 'activo', 'created_by' => 1],
            );

            foreach ($servicios as $servicio) {
                ServicioEspecialidad::firstOrCreate(
                    [
                        'especialidad_id' => $especialidad->id,
                        'nombre'          => $servicio['nombre'],
                    ],
                    [
                        'precio'     => $servicio['precio'],
                        'estado'     => 'activo',
                        'created_by' => 1,
                    ],
                );
            }
        }
    }
}
