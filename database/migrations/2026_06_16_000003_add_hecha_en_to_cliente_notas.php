<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de la nota como tarea: pendiente (hecha_en NULL) o hecha
     * (hecha_en con la fecha en que se resolvió). Se archiva, no se borra,
     * para conservar el historial clínico de lo que estaba pendiente.
     *
     * `leida` se mantiene: lo usa el flujo de asistencia (avisar notas sin
     * leer cuando el paciente llega). Son conceptos distintos.
     */
    public function up(): void
    {
        Schema::table('cliente_notas', function (Blueprint $table) {
            $table->timestamp('hecha_en')->nullable()->after('leida');
            $table->index(['cliente_id', 'hecha_en']); // contar/filtrar pendientes
        });
    }

    public function down(): void
    {
        Schema::table('cliente_notas', function (Blueprint $table) {
            $table->dropIndex(['cliente_id', 'hecha_en']);
            $table->dropColumn('hecha_en');
        });
    }
};
