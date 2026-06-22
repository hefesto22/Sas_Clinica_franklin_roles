<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula una condición del odontograma con la HOJA que la generó.
     * Permite re-sincronizar: al guardar una hoja, se reemplazan solo las
     * condiciones que esa hoja había aportado, sin tocar las demás.
     */
    public function up(): void
    {
        Schema::table('evaluacion_detalle_condiciones', function (Blueprint $table) {
            $table->foreignId('origen_evaluacion_id')
                ->nullable()
                ->after('evaluacion_detalle_id')
                ->constrained('evaluaciones', indexName: 'edc_origen_evaluacion_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_detalle_condiciones', function (Blueprint $table) {
            $table->dropForeign('edc_origen_evaluacion_fk');
            $table->dropColumn('origen_evaluacion_id');
        });
    }
};
