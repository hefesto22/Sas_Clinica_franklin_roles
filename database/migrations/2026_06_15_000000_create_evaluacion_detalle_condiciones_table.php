<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log de condiciones por pieza dental.
     *
     * Reemplaza al JSON `condiciones` (mapa condicion => tratada) por filas
     * normalizadas: cada condición lleva su propia nota y su fecha de
     * tratamiento, y la MISMA condición puede repetirse en el tiempo sobre
     * la misma pieza (recurrencia: el paciente vuelve por caries en el mismo
     * diente). Por eso NO hay unique sobre (evaluacion_detalle_id, condicion).
     */
    public function up(): void
    {
        // Limpia una posible tabla parcial de un intento previo fallido
        // (la creación dejó la tabla, pero falló al crear el índice).
        Schema::dropIfExists('evaluacion_detalle_condiciones');

        Schema::create('evaluacion_detalle_condiciones', function (Blueprint $table) {
            $table->id();

            // La pieza dental es el ancla; borrar la pieza arrastra su historial.
            // Nombres de FK/índice explícitos: el autogenerado supera el límite
            // de 64 caracteres de los identificadores de MySQL.
            $table->foreignId('evaluacion_detalle_id')
                ->constrained('evaluacion_detalles', indexName: 'edc_detalle_fk')
                ->cascadeOnDelete();

            $table->string('condicion', 30);          // clave del catálogo EvaluacionDetalle::CONDICIONES
            $table->text('nota')->nullable();          // detalle clínico por condición
            $table->boolean('tratada')->default(false);
            $table->date('detectada_en')->nullable();  // cuándo se registró la condición
            $table->date('tratada_en')->nullable();    // cuándo se trató (null = pendiente)

            $table->timestamps();
            $table->softDeletes();                     // registro clínico se archiva, no se borra

            // Índices para las consultas reales del odontograma y los reportes.
            $table->index(['evaluacion_detalle_id', 'condicion'], 'edc_detalle_condicion_idx'); // condiciones de una pieza
            $table->index(['condicion', 'tratada_en'], 'edc_condicion_tratada_en_idx');         // reportes por tipo/período
            $table->index('tratada', 'edc_tratada_idx');                                        // filtrar pendientes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_detalle_condiciones');
    }
};
