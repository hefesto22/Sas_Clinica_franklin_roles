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
        // Tabla especialidades
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabla servicios
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidad_id')->constrained('especialidades')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Restricción de unicidad: no se pueden repetir servicios dentro de la misma especialidad
            $table->unique(['especialidad_id', 'nombre']);
        });
        // Tabla clientes (expediente clínico)
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');                     // requerido
            $table->string('dni')->unique();              // requerido, único
            $table->string('telefono')->nullable();       // contacto principal
            $table->string('direccion')->nullable();      // domicilio
            $table->string('ocupacion')->nullable();      // trabajo/profesión
            $table->date('fecha_nacimiento')->nullable(); // calcular edad automáticamente

            // Contacto de emergencia
            $table->string('contacto_emergencia_nombre')->nullable();
            $table->string('contacto_emergencia_telefono')->nullable();

            // Datos clínicos rápidos
            $table->text('motivo_consulta')->nullable();
            $table->text('alergias')->nullable();

            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        // Tabla actividades del cliente (expediente)
        Schema::create('cliente_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('actividad');
            $table->decimal('pago', 10, 2)->nullable();
            $table->timestamps();
        });
        // Tabla notas rápidas del cliente
        Schema::create('cliente_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            $table->text('contenido');  // la nota / sugerencia
            $table->boolean('leida')->default(false); // false = no leída, true = leída

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // índices útiles
            $table->index(['cliente_id', 'leida']);
        });
        // Tabla imágenes del cliente (expediente)
        Schema::create('cliente_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('path'); // ruta de imagen subida
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_imagenes');
        Schema::dropIfExists('cliente_notas');
        Schema::dropIfExists('cliente_actividades');
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('especialidades');
        Schema::dropIfExists('clientes');
    }
};
