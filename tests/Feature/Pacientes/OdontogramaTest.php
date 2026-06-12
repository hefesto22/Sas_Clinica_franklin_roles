<?php

use App\Livewire\Odontograma;
use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Livewire\Livewire;

/**
 * Odontograma interactivo (notación FDI con punto, como guarda producción).
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

function usuarioOdontograma(string $rol): User
{
    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

it('el doctor registra un diagnóstico desde el odontograma', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '1.8')
        ->set('diagnostico', 'Caries oclusal')
        ->set('hecho', false)
        ->call('guardar');

    $detalle = $evaluacion->detalles()->where('pieza', '1.8')->first();

    expect($detalle)->not->toBeNull()
        ->and($detalle->diagnostico)->toBe('Caries oclusal')
        ->and($detalle->hecho)->toBeFalse();
});

it('marcar tratamiento realizado cambia el estado de la pieza a hecho', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();
    EvaluacionDetalle::factory()->create([
        'evaluacion_id' => $evaluacion->id,
        'pieza'         => '2.4',
        'diagnostico'   => 'Obturación',
        'hecho'         => false,
    ]);

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '2.4')
        ->set('hecho', true)
        ->call('guardar');

    expect($evaluacion->detalles()->where('pieza', '2.4')->first()->hecho)->toBeTrue();
});

it('vaciar el diagnóstico elimina el registro de la pieza (queda sana)', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();
    EvaluacionDetalle::factory()->create([
        'evaluacion_id' => $evaluacion->id,
        'pieza'         => '3.6',
        'diagnostico'   => 'Caries',
    ]);

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '3.6')
        ->set('diagnostico', '   ')
        ->call('guardar');

    expect($evaluacion->detalles()->where('pieza', '3.6')->exists())->toBeFalse();
});

it('el asistente no puede editar desde el odontograma (solo registra evaluaciones nuevas)', function () {
    $this->actingAs(usuarioOdontograma('asistente'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '1.1')
        ->set('diagnostico', 'Intento sin permiso')
        ->call('guardar');

    expect($evaluacion->detalles()->where('pieza', '1.1')->exists())->toBeFalse();
});

it('registra una condición del catálogo con su color en el odontograma', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '1.6')
        ->set('condiciones', ['caries'])
        ->set('diagnostico', 'Oclusal profunda')
        ->call('guardar');

    $detalle = $evaluacion->detalles()->where('pieza', '1.6')->first();

    expect($detalle->condiciones)->toBe(['caries' => false])
        ->and($detalle->diagnostico)->toBe('Oclusal profunda');
});

it('una pieza puede tener VARIAS condiciones a la vez', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '4.5')
        ->set('condiciones', ['caries', 'fractura', 'extraccion_indicada'])
        ->call('guardar');

    expect($evaluacion->detalles()->where('pieza', '4.5')->first()->condiciones)
        ->toBe(['caries' => false, 'fractura' => false, 'extraccion_indicada' => false]);
});

it('cada condición lleva su propio estado: tratar solo una no marca la pieza completa', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '4.4')
        ->set('condiciones', ['caries', 'fractura'])
        ->set('tratadas', ['caries']) // solo la caries fue tratada
        ->call('guardar');

    $detalle = $evaluacion->detalles()->where('pieza', '4.4')->first();

    expect($detalle->condiciones)->toBe(['caries' => true, 'fractura' => false])
        ->and($detalle->hecho)->toBeFalse(); // la pieza NO está completa
});

it('la pieza queda hecha cuando TODAS sus condiciones están tratadas', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '4.4')
        ->set('condiciones', ['caries', 'fractura'])
        ->set('tratadas', ['caries', 'fractura'])
        ->call('guardar');

    $detalle = $evaluacion->detalles()->where('pieza', '4.4')->first();

    expect($detalle->condiciones)->toBe(['caries' => true, 'fractura' => true])
        ->and($detalle->hecho)->toBeTrue();
});

it('descarta condiciones fuera del catálogo y conserva las válidas', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->set('condiciones', ['condicion_falsa', 'caries'])
        ->call('seleccionar', '1.6')
        ->set('condiciones', ['condicion_falsa', 'caries'])
        ->call('guardar');

    expect($evaluacion->detalles()->where('pieza', '1.6')->first()->condiciones)->toBe(['caries' => false]);
});

it('una condición sin nota también se guarda (ej: pieza ausente)', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();

    Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion])
        ->call('seleccionar', '3.8')
        ->set('condiciones', ['ausente'])
        ->set('diagnostico', '')
        ->call('guardar');

    expect($evaluacion->detalles()->where('pieza', '3.8')->first()->condiciones)->toBe(['ausente' => false]);
});

it('refleja el estado de cada pieza: vacío, pendiente y hecho', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $evaluacion = Evaluacion::factory()->create();
    EvaluacionDetalle::factory()->create([
        'evaluacion_id' => $evaluacion->id, 'pieza' => '1.1', 'diagnostico' => 'Caries', 'hecho' => false,
    ]);
    EvaluacionDetalle::factory()->create([
        'evaluacion_id' => $evaluacion->id, 'pieza' => '1.2', 'diagnostico' => 'Sellante', 'hecho' => true,
    ]);

    $componente = Livewire::test(Odontograma::class, ['evaluacion' => $evaluacion]);

    expect($componente->instance()->estadoDe('1.1'))->toBe('pendiente')
        ->and($componente->instance()->estadoDe('1.2'))->toBe('hecho')
        ->and($componente->instance()->estadoDe('4.8'))->toBe('vacio');
});
