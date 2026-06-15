<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina la columna JSON `condiciones` de evaluacion_detalles.
     *
     * Quedó redundante tras migrar a la tabla normalizada
     * evaluacion_detalle_condiciones (ya respaldada por el backfill). El
     * diagnóstico de hoja (`diagnostico`) y `hecho` se mantienen: pertenecen
     * al formato de hoja, no al odontograma.
     */
    public function up(): void
    {
        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->dropColumn('condiciones');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->json('condiciones')->nullable()->after('pieza');
        });
    }
};
