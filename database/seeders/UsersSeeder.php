<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Usuarios base del sistema con sus roles.
 *
 * ⚠️ Solo para entornos de desarrollo/staging: las contraseñas son
 * genéricas (12345678). En producción crear usuarios reales y cambiar
 * las contraseñas inmediatamente.
 */
class UsersSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Roles de la clínica + roles de Shield
        $roles = ['super_admin', 'panel_user', 'doctor', 'recepcion', 'asistente'];

        foreach ($roles as $rol) {
            Role::findOrCreate($rol, 'web');
        }

        $usuarios = [
            ['name' => 'Administrador', 'email' => 'admin@gmail.com',     'roles' => ['super_admin']],
            ['name' => 'Doctor',        'email' => 'doctor@gmail.com',    'roles' => ['doctor', 'panel_user']],
            ['name' => 'Recepción',     'email' => 'recepcion@gmail.com', 'roles' => ['recepcion', 'panel_user']],
            ['name' => 'Asistente',     'email' => 'asistente@gmail.com', 'roles' => ['asistente', 'panel_user']],
        ];

        foreach ($usuarios as $datos) {
            $user = User::firstOrCreate(
                ['email' => $datos['email']],
                [
                    'name'     => $datos['name'],
                    'password' => Hash::make('12345678'),
                ],
            );

            $user->syncRoles($datos['roles']);
        }
    }
}
