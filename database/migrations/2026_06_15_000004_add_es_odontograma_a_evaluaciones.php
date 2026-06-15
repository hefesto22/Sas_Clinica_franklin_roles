<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca la Evaluacion que actúa como ODONTOGRAMA ÚNICO del paciente,
     * separándola de las hojas por visita. Así crear una hoja nueva nunca
     * cambia el contenedor del odontograma (antes era frágil: dependía de
     * la fecha de la última evaluación).
     */
    public function up(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->boolean('es_odontograma')->default(false)->after('cliente_id');
            // Un paciente busca su odontograma por (cliente_id, es_odontograma).
            $table->index(['cliente_id', 'es_odontograma'], 'eval_cliente_odontograma_idx');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropIndex('eval_cliente_odontograma_idx');
            $table->dropColumn('es_odontograma');
        });
    }
};
