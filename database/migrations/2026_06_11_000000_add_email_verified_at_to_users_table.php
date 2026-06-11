<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restaura email_verified_at: la migración de users fue personalizada y
     * la quitó, pero el código del starter kit (Livewire/Settings/Profile,
     * rutas de verificación, UserFactory) todavía la referencia.
     *
     * Nullable y sin efecto en login: User no implementa MustVerifyEmail.
     * Guard con hasColumn por si la BD de producción aún conserva la columna.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('email_verified_at');
            });
        }
    }
};
