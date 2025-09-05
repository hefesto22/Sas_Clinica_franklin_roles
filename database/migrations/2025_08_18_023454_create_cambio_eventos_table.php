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
        // Tabla de cambios de evento
        Schema::create('cambio_eventos', function (Blueprint $table) {
            $table->id();

            // Eventos involucrados
            $table->foreignId('evento_id_origen')->constrained('events')->cascadeOnDelete();
            $table->foreignId('evento_id_destino')->constrained('events')->cascadeOnDelete();

            // Usuario que propone el cambio
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Usuario que acepta o rechaza (opcional, nullable)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Estado del proceso
            $table->enum('estado', ['pendiente', 'aceptado', 'rechazado', 'cancelado'])->default('pendiente');

            // Campos auxiliares
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamp('rechazado_en')->nullable();
            $table->timestamp('cancelado_en')->nullable();

            $table->timestamps();
        });

        // Tabla evaluaciones (hojas por paciente)
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->date('fecha')->default(now());
            $table->string('limpieza_periodontal')->nullable();
            $table->string('fluor')->nullable();
            $table->longText('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabla detalles por pieza dental
        Schema::create('evaluacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->cascadeOnDelete();
            $table->string('pieza', 3); // ej. “18”, “11”, “21”, etc.
            $table->text('diagnostico')->nullable();
            $table->timestamps();

            $table->unique(['evaluacion_id', 'pieza']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_detalles');
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('cambio_eventos');
    }
};
