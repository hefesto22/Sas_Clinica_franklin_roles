<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de eventos (ahora representa una cita o atención)
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Relaciones clave foránea
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('consultorio_id')->constrained('consultorios')->onDelete('cascade');

            // Teléfono y estado
            $table->string('telefono')->nullable();
            $table->enum('estado', ['Pendiente', 'Reagendando', 'Reagendado', 'Confirmado', 'Se Presentó'])->default('Pendiente');

            // Fechas de la cita
            $table->dateTime('start_at');
            $table->dateTime('end_at');

            // Auditoría
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });

        // Tabla pivote event_especialidad
        Schema::create('event_especialidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('especialidad_id')->constrained('especialidades')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['event_id', 'especialidad_id']);
        });

        // Tabla pivote event_servicio
        Schema::create('event_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['event_id', 'servicio_id']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_servicio');
        Schema::dropIfExists('event_especialidad');
        Schema::dropIfExists('events');
    }
};
