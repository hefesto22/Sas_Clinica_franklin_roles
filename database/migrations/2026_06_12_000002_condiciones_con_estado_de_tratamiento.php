<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cada condición lleva su propio estado de tratamiento:
     * ["caries","fractura"]  →  {"caries": false, "fractura": false}
     * (el valor hereda el 'hecho' general que tuviera la pieza).
     */
    public function up(): void
    {
        DB::table('evaluacion_detalles')
            ->whereNotNull('condiciones')
            ->orderBy('id')
            ->each(function ($detalle) {
                $condiciones = json_decode($detalle->condiciones, true);

                if (! is_array($condiciones) || $condiciones === [] || ! array_is_list($condiciones)) {
                    return; // ya es mapa o está vacío
                }

                $mapa = array_fill_keys($condiciones, (bool) $detalle->hecho);

                DB::table('evaluacion_detalles')
                    ->where('id', $detalle->id)
                    ->update(['condiciones' => json_encode($mapa)]);
            });
    }

    public function down(): void
    {
        DB::table('evaluacion_detalles')
            ->whereNotNull('condiciones')
            ->orderBy('id')
            ->each(function ($detalle) {
                $condiciones = json_decode($detalle->condiciones, true);

                if (! is_array($condiciones) || array_is_list($condiciones)) {
                    return;
                }

                DB::table('evaluacion_detalles')
                    ->where('id', $detalle->id)
                    ->update(['condiciones' => json_encode(array_keys($condiciones))]);
            });
    }
};
