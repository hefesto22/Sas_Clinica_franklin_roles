<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migra el JSON `condiciones` (lista vieja o mapa condicion => tratada)
     * a filas de evaluacion_detalle_condiciones, sin perder historial.
     *
     * - detectada_en / tratada_en: se aproximan con la fecha de la evaluación,
     *   la mejor señal temporal disponible al momento del backfill.
     * - La nota libre `diagnostico` se mantiene a nivel de pieza (no se reparte
     *   entre condiciones porque era compartida y no sabríamos a cuál pertenece).
     *
     * NO toca ni elimina la columna `condiciones`: queda como respaldo hasta
     * una migración de limpieza posterior, una vez validado el nuevo flujo.
     */
    public function up(): void
    {
        DB::table('evaluacion_detalles as d')
            ->join('evaluaciones as e', 'e.id', '=', 'd.evaluacion_id')
            ->whereNotNull('d.condiciones')
            ->orderBy('d.id')
            ->select('d.id', 'd.condiciones', 'e.fecha')
            ->each(function ($detalle) {
                $decoded = json_decode($detalle->condiciones, true);

                if (! is_array($decoded) || $decoded === []) {
                    return;
                }

                // Normaliza: lista vieja (["caries"]) o mapa ({"caries": true}).
                $mapa = array_is_list($decoded)
                    ? array_fill_keys($decoded, false)
                    : array_map(fn ($v) => (bool) $v, $decoded);

                $ahora = now();
                $fecha = $detalle->fecha; // date de la evaluación

                $filas = [];
                foreach ($mapa as $condicion => $tratada) {
                    $filas[] = [
                        'evaluacion_detalle_id' => $detalle->id,
                        'condicion'             => $condicion,
                        'nota'                  => null,
                        'tratada'               => $tratada,
                        'detectada_en'          => $fecha,
                        'tratada_en'            => $tratada ? $fecha : null,
                        'created_at'            => $ahora,
                        'updated_at'            => $ahora,
                    ];
                }

                if ($filas !== []) {
                    DB::table('evaluacion_detalle_condiciones')->insert($filas);
                }
            });
    }

    /**
     * Reversible: vaciar las filas backfilled. Como el rollback de la
     * migración anterior elimina la tabla completa, aquí basta con limpiar.
     */
    public function down(): void
    {
        DB::table('evaluacion_detalle_condiciones')->delete();
    }
};
