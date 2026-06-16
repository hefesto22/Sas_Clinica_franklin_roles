<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo de paciente: general u ortodoncia. Los pacientes existentes
     * quedan como 'general' por defecto y se reclasifican desde el panel.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->enum('tipo_paciente', ['general', 'ortodoncia'])
                ->default('general')
                ->after('dni');

            $table->index('tipo_paciente'); // filtrar pacientes por tipo
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['tipo_paciente']);
            $table->dropColumn('tipo_paciente');
        });
    }
};
