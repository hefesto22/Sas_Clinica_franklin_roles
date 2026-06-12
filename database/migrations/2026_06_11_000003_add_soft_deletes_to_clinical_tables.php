<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los registros clínicos se ARCHIVAN, no se destruyen:
     * "eliminar" un paciente, cita o evaluación ahora es un soft delete
     * (deleted_at). El borrado físico (forceDelete) queda reservado y
     * sigue protegido por las FKs restrict.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('events', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('evaluaciones', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
