<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * super_admin con TODOS los permisos asignados explícitamente.
 *
 * 1) shield:generate crea el catálogo completo de permisos de todas las
 *    entidades del panel (resources, pages, widgets) sin tocar policies.
 * 2) Se asegura que existan también los permisos custom del config.
 * 3) super_admin recibe el catálogo entero (visible en el editor de roles).
 *
 * El Gate::before de Shield se mantiene como red de seguridad: si mañana
 * se agrega una entidad nueva y nadie regenera, super_admin no pierde acceso.
 */
class SuperAdminPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Catálogo completo de permisos del panel (solo permisos, no policies)
        Artisan::call('shield:generate', [
            '--all'    => true,
            '--option' => 'permissions',
            '--panel'  => 'admin',
        ]);

        // 2) Permisos custom (modelos del expediente sin Resource propio)
        foreach (array_keys(config('filament-shield.custom_permissions', [])) as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 3) Todo el catálogo para super_admin
        Role::findOrCreate('super_admin', 'web')
            ->syncPermissions(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
