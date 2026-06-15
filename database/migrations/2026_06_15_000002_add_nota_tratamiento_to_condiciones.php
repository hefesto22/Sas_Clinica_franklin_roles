<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota opcional de TRATAMIENTO: qué se hizo al tratar la condición.
     * Es distinta de `nota` (hallazgo/detección): una describe el problema,
     * la otra el procedimiento realizado.
     */
    public function up(): void
    {
        Schema::table('evaluacion_detalle_condiciones', function (Blueprint $table) {
            $table->text('nota_tratamiento')->nullable()->after('nota');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_detalle_condiciones', function (Blueprint $table) {
            $table->dropColumn('nota_tratamiento');
        });
    }
};
