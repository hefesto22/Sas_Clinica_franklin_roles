<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo de la actividad realizada: general u ortodoncia. Opcional
     * (nullable): las actividades históricas y las que vienen de asistencia
     * pueden quedar sin tipo. Da claridad y permite filtrar el expediente.
     */
    public function up(): void
    {
        Schema::table('cliente_actividades', function (Blueprint $table) {
            $table->enum('tipo', ['general', 'ortodoncia'])
                ->nullable()
                ->after('actividad');

            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('cliente_actividades', function (Blueprint $table) {
            $table->dropIndex(['tipo']);
            $table->dropColumn('tipo');
        });
    }
};
