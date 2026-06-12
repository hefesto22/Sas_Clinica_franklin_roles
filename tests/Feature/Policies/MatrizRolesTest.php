<?php

use App\Models\Cliente;
use App\Models\Evaluacion;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Gate;

/**
 * Matriz de permisos por rol (decisiones de negocio 2026-06-11).
 * Si este test falla, la matriz del RolesYPermisosSeeder cambió:
 * confirmar con el negocio antes de ajustar.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

function usuarioConRol(string $rol): User
{
    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

it('recepción gestiona agenda y pacientes pero NO ve el detalle clínico', function () {
    $recepcion  = usuarioConRol('recepcion');
    $cliente    = Cliente::factory()->create();
    $evaluacion = Evaluacion::factory()->create();

    expect(Gate::forUser($recepcion)->allows('create', Event::class))->toBeTrue()
        ->and(Gate::forUser($recepcion)->allows('update', Event::factory()->create()))->toBeTrue()
        ->and(Gate::forUser($recepcion)->allows('view', $cliente))->toBeTrue()
        ->and(Gate::forUser($recepcion)->allows('create', Cliente::class))->toBeTrue()
        // Detalle clínico bloqueado
        ->and(Gate::forUser($recepcion)->allows('viewAny', Evaluacion::class))->toBeFalse()
        ->and(Gate::forUser($recepcion)->allows('view', $evaluacion))->toBeFalse()
        ->and(Gate::forUser($recepcion)->allows('viewAny', \App\Models\ClienteNota::class))->toBeFalse()
        ->and(Gate::forUser($recepcion)->allows('viewAny', \App\Models\ClienteImagen::class))->toBeFalse();
});

it('doctor ve y gestiona el expediente clínico completo pero no elimina', function () {
    $doctor     = usuarioConRol('doctor');
    $evaluacion = Evaluacion::factory()->create();
    $cita       = Event::factory()->create();

    expect(Gate::forUser($doctor)->allows('view', $evaluacion))->toBeTrue()
        ->and(Gate::forUser($doctor)->allows('create', Evaluacion::class))->toBeTrue()
        ->and(Gate::forUser($doctor)->allows('update', $evaluacion))->toBeTrue()
        ->and(Gate::forUser($doctor)->allows('delete', $evaluacion))->toBeFalse()
        ->and(Gate::forUser($doctor)->allows('update', $cita))->toBeTrue()
        ->and(Gate::forUser($doctor)->allows('delete', $cita))->toBeFalse();
});

it('asistente ve y registra evaluaciones pero no las edita', function () {
    $asistente  = usuarioConRol('asistente');
    $evaluacion = Evaluacion::factory()->create();

    expect(Gate::forUser($asistente)->allows('view', $evaluacion))->toBeTrue()
        ->and(Gate::forUser($asistente)->allows('create', Evaluacion::class))->toBeTrue()
        ->and(Gate::forUser($asistente)->allows('update', $evaluacion))->toBeFalse()
        ->and(Gate::forUser($asistente)->allows('delete', $evaluacion))->toBeFalse()
        // Tampoco crea citas ni pacientes
        ->and(Gate::forUser($asistente)->allows('create', Event::class))->toBeFalse()
        ->and(Gate::forUser($asistente)->allows('create', Cliente::class))->toBeFalse();
});

it('nadie excepto super_admin elimina citas ni pacientes ni toca el catálogo', function (string $rol) {
    $user    = usuarioConRol($rol);
    $cita    = Event::factory()->create();
    $cliente = Cliente::factory()->create();

    expect(Gate::forUser($user)->allows('delete', $cita))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $cliente))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', \App\Models\Consultorio::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', \App\Models\Especialidad::class))->toBeFalse();
})->with(['recepcion', 'doctor', 'asistente']);

it('super_admin pasa por el Gate sin permisos explícitos', function () {
    \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
    $admin = usuarioConRol('super_admin');

    $evaluacion = Evaluacion::factory()->create();

    expect(Gate::forUser($admin)->allows('delete', $evaluacion))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', \App\Models\Consultorio::class))->toBeTrue();
});
