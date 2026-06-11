<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices compuestos para las queries reales de la agenda.
     * Sin ellos, cada carga del calendario y cada verificación de
     * disponibilidad hace full scan cuando la tabla crece.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['consultorio_id', 'start_at'], 'events_consultorio_start_idx'); // disponibilidad por consultorio
            $table->index(['doctor_id', 'start_at'], 'events_doctor_start_idx');           // agenda por doctor
            $table->index(['cliente_id', 'start_at'], 'events_cliente_start_idx');         // historial y regla de 25 días
            $table->index(['estado', 'start_at'], 'events_estado_start_idx');              // reportes por estado/período
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_consultorio_start_idx');
            $table->dropIndex('events_doctor_start_idx');
            $table->dropIndex('events_cliente_start_idx');
            $table->dropIndex('events_estado_start_idx');
        });
    }
};
