<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DNI opcional: algunos pacientes llegan sin identidad y se completa
     * después. El índice único se mantiene — en MySQL un UNIQUE permite
     * varios NULL, así que la unicidad sigue valiendo cuando el DNI existe.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('dni')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('dni')->nullable(false)->change();
        });
    }
};
