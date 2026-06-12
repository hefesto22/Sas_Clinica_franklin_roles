<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Condición clínica de la pieza (caries, corona, implante, ausente...).
     * El diagnóstico libre se mantiene como nota complementaria.
     */
    public function up(): void
    {
        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->string('condicion', 30)->nullable()->after('pieza');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->dropColumn('condicion');
        });
    }
};
