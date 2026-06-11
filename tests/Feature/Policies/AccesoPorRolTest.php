<?php

use App\Models\Cliente;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Control de acceso por rol (Shield + spatie/laravel-permission).
 * El expediente clínico es información médica sensible: el acceso
 * se decide en las Policies, nunca por ifs sueltos.
 */
function rolConPermisos(string $nombre, array $permisos): Role
{
    $rol = Role::create(['name' => $nombre, 'guard_name' => 'web']);

    foreach ($permisos as $permiso) {
        $rol->givePermissionTo(
            Permission::findOrCreate($permiso, 'web')
        );
    }

    return $rol;
}

it('un usuario sin permisos no puede ver ni crear expedientes de clientes', function () {
    $user    = User::factory()->create();
    $cliente = Cliente::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Cliente::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $cliente))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', Cliente::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $cliente))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $cliente))->toBeFalse();
});

it('un rol con permisos de cliente puede gestionar expedientes', function () {
    $rol = rolConPermisos('recepcion_test', [
        'view_any_cliente', 'view_cliente', 'create_cliente', 'update_cliente',
    ]);

    $user = User::factory()->create();
    $user->assignRole($rol);

    $cliente = Cliente::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Cliente::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $cliente))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Cliente::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $cliente))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $cliente))->toBeFalse(); // no se le dio delete
});

it('un usuario sin permisos no puede gestionar citas', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Event::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $event))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $event))->toBeFalse();
});

it('un rol con permisos de citas puede verlas y editarlas pero no borrarlas', function () {
    $rol = rolConPermisos('asistente_test', [
        'view_any_event', 'view_event', 'update_event',
    ]);

    $user = User::factory()->create();
    $user->assignRole($rol);

    $event = Event::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Event::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $event))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $event))->toBeFalse();
});
