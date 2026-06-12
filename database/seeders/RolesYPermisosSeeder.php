<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matriz de permisos por rol — decisiones de negocio confirmadas por
 * Mauricio el 2026-06-11:
 *
 * - Recepción NO ve detalle clínico (evaluaciones, notas, imágenes);
 *   sí gestiona agenda, pacientes (contacto) y ve actividades/pagos.
 * - Asistente ve y REGISTRA evaluaciones, pero no las edita ni borra.
 * - Eliminar citas: solo super_admin (los demás cancelan/reagendan).
 * - Catálogo (consultorios, especialidades, servicios): solo super_admin.
 * - super_admin no necesita permisos: pasa por Gate::before (Shield).
 *
 * Idempotente: puede correrse las veces que sea (syncPermissions).
 */
class RolesYPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matriz = [
            'recepcion' => [
                // Agenda
                'view_any_event', 'view_event', 'create_event', 'update_event',
                // Solicitudes de intercambio (crear; aprobar es del doctor)
                'view_any_cambio_evento', 'view_cambio_evento', 'create_cambio_evento',
                // Pacientes: datos de contacto, sin detalle clínico
                'view_any_cliente', 'view_cliente', 'create_cliente', 'update_cliente',
                // Actividades/pagos (cobranza)
                'view_any_cliente_actividad', 'view_cliente_actividad',
            ],

            'doctor' => [
                // Agenda
                'view_any_event', 'view_event', 'create_event', 'update_event',
                // Intercambios: crear y aprobar/rechazar
                'view_any_cambio_evento', 'view_cambio_evento', 'create_cambio_evento', 'update_cambio_evento',
                // Pacientes
                'view_any_cliente', 'view_cliente', 'create_cliente', 'update_cliente',
                // Detalle clínico completo (sin borrar: los registros se archivan)
                'view_any_evaluacion', 'view_evaluacion', 'create_evaluacion', 'update_evaluacion',
                'view_any_cliente_nota', 'view_cliente_nota', 'create_cliente_nota', 'update_cliente_nota',
                'view_any_cliente_imagen', 'view_cliente_imagen', 'create_cliente_imagen',
                'view_any_cliente_actividad', 'view_cliente_actividad', 'create_cliente_actividad',
            ],

            'asistente' => [
                // Solo consulta de agenda y pacientes
                'view_any_event', 'view_event',
                'view_any_cliente', 'view_cliente',
                // Evaluaciones: ver y registrar, sin editar ni borrar
                'view_any_evaluacion', 'view_evaluacion', 'create_evaluacion',
                'view_any_cliente_nota', 'view_cliente_nota', 'create_cliente_nota',
                'view_any_cliente_imagen', 'view_cliente_imagen', 'create_cliente_imagen',
                'view_any_cliente_actividad', 'view_cliente_actividad',
            ],
        ];

        foreach ($matriz as $rolNombre => $permisos) {
            $rol = Role::findOrCreate($rolNombre, 'web');

            $rol->syncPermissions(
                collect($permisos)
                    ->map(fn (string $permiso) => Permission::findOrCreate($permiso, 'web'))
                    ->all()
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
