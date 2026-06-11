<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Endurece las FKs de registros clínicos: borrar un cliente, consultorio
     * o usuario NO debe borrar en cascada el historial de citas ni el
     * odontograma. Con restrict, MySQL rechaza el borrado si hay historial.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['consultorio_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
            $table->foreign('consultorio_id')->references('id')->on('consultorios')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['consultorio_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('consultorio_id')->references('id')->on('consultorios')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
        });
    }
};
