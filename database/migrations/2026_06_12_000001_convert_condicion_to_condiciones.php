<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una pieza puede tener VARIAS condiciones a la vez (ej: caries +
     * fractura). Se convierte la columna única en una lista JSON.
     */
    public function up(): void
    {
        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->json('condiciones')->nullable()->after('pieza');
        });

        DB::statement(
            'UPDATE evaluacion_detalles SET condiciones = JSON_ARRAY(condicion) WHERE condicion IS NOT NULL'
        );

        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->dropColumn('condicion');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->string('condicion', 30)->nullable()->after('pieza');
        });

        DB::statement(
            "UPDATE evaluacion_detalles SET condicion = JSON_UNQUOTE(JSON_EXTRACT(condiciones, '$[0]')) WHERE condiciones IS NOT NULL"
        );

        Schema::table('evaluacion_detalles', function (Blueprint $table) {
            $table->dropColumn('condiciones');
        });
    }
};
