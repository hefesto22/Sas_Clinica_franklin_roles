<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tamaño/extensión de la condición: pequeña, mediana o grande
     * (lo que la clínica registra como obturación 1/2/3). Opcional: no
     * todas las condiciones llevan tamaño. A futuro define el cobro.
     */
    public function up(): void
    {
        Schema::table('evaluacion_detalle_condiciones', function (Blueprint $table) {
            $table->enum('tamano', ['pequena', 'mediana', 'grande'])
                ->nullable()
                ->after('nota');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_detalle_condiciones', function (Blueprint $table) {
            $table->dropColumn('tamano');
        });
    }
};
