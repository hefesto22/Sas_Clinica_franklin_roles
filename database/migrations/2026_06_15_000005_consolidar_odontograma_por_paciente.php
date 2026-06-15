<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Consolida el odontograma de cada paciente en su Evaluacion dedicada.
     *
     * Datos previos: las condiciones colgaban de la evaluación que tuviera el
     * paciente al momento (a veces una hoja). Aquí, por cada paciente con
     * condiciones, se asegura UNA evaluación es_odontograma y se MUEVEN sus
     * condiciones al detalle (pieza) canónico bajo ella. Se mueven solo las
     * filas de condición: el diagnóstico de hoja de cada detalle se queda
     * donde está (no se mezcla con el odontograma).
     *
     * Se usa el query builder (no modelos) para no disparar auditoría.
     */
    public function up(): void
    {
        $clienteIds = DB::table('evaluacion_detalle_condiciones as c')
            ->join('evaluacion_detalles as d', 'd.id', '=', 'c.evaluacion_detalle_id')
            ->join('evaluaciones as e', 'e.id', '=', 'd.evaluacion_id')
            ->whereNull('c.deleted_at')
            ->distinct()
            ->pluck('e.cliente_id');

        foreach ($clienteIds as $clienteId) {
            $odontogramaId = $this->odontogramaDePaciente($clienteId);

            $condiciones = DB::table('evaluacion_detalle_condiciones as c')
                ->join('evaluacion_detalles as d', 'd.id', '=', 'c.evaluacion_detalle_id')
                ->join('evaluaciones as e', 'e.id', '=', 'd.evaluacion_id')
                ->where('e.cliente_id', $clienteId)
                ->where('d.evaluacion_id', '!=', $odontogramaId)
                ->select('c.id as cond_id', 'd.pieza')
                ->get();

            foreach ($condiciones as $condicion) {
                $detalleId = $this->detalleCanonico($odontogramaId, $condicion->pieza);

                DB::table('evaluacion_detalle_condiciones')
                    ->where('id', $condicion->cond_id)
                    ->update(['evaluacion_detalle_id' => $detalleId, 'updated_at' => now()]);
            }
        }
    }

    /** Devuelve (o crea) la Evaluacion dedicada al odontograma del paciente. */
    private function odontogramaDePaciente(int $clienteId): int
    {
        $id = DB::table('evaluaciones')
            ->where('cliente_id', $clienteId)
            ->where('es_odontograma', true)
            ->value('id');

        if ($id) {
            return $id;
        }

        return DB::table('evaluaciones')->insertGetId([
            'cliente_id'     => $clienteId,
            'es_odontograma' => true,
            'fecha'          => now()->toDateString(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /** Detalle (pieza) canónico bajo el odontograma; lo crea si no existe. */
    private function detalleCanonico(int $odontogramaId, string $pieza): int
    {
        $id = DB::table('evaluacion_detalles')
            ->where('evaluacion_id', $odontogramaId)
            ->where('pieza', $pieza)
            ->value('id');

        if ($id) {
            return $id;
        }

        return DB::table('evaluacion_detalles')->insertGetId([
            'evaluacion_id' => $odontogramaId,
            'pieza'         => $pieza,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * No reversible de forma segura: la procedencia original de cada condición
     * no se preserva. El rollback de las migraciones de esquema basta.
     */
    public function down(): void
    {
        // no-op
    }
};
