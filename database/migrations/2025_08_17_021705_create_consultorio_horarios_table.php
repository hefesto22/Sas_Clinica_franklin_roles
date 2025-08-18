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
        // Tabla consultorios
        Schema::create('consultorios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('modo_defecto', ['horario', 'cupos'])->default('horario');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabla consultorio_turnos (horarios/días del consultorio)
        Schema::create('consultorio_turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultorio_id')->constrained('consultorios')->cascadeOnDelete();

            $table->unsignedTinyInteger('dia_semana'); // 1 = Lunes ... 7 = Domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->enum('modo', ['horario', 'cupos'])->nullable(); // Si es null, usa modo_defecto del consultorio
            $table->unsignedSmallInteger('slot_minutos')->nullable(); // Ej. 30 (solo para modo horario)
            $table->unsignedSmallInteger('cupos_por_hora')->nullable(); // Ej. 6 (solo para modo cupos)

            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Para evitar duplicados exactos en un mismo consultorio
            $table->unique(['consultorio_id', 'dia_semana', 'hora_inicio', 'hora_fin'], 'turno_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultorio_turnos');
        Schema::dropIfExists('consultorios');
    }
};
