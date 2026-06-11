<?php

use App\Models\CambioEvento;
use App\Models\Cliente;
use App\Models\ClienteActividad;
use App\Models\ClienteImagen;
use App\Models\ClienteNota;
use App\Models\Consultorio;
use App\Models\ConsultorioTurno;
use App\Models\Especialidad;
use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use App\Models\Event;
use App\Models\ServicioEspecialidad;

/**
 * Sanidad de factories: cada modelo del dominio se puede fabricar
 * y sus relaciones clave funcionan. Es la base de toda la suite
 * y la red de seguridad del upgrade a Filament v4.
 */
it('fabrica todos los modelos del dominio sin errores', function (string $model) {
    expect($model::factory()->create())->toBeInstanceOf($model);
})->with([
    Cliente::class,
    Consultorio::class,
    ConsultorioTurno::class,
    Especialidad::class,
    ServicioEspecialidad::class,
    Event::class,
    Evaluacion::class,
    EvaluacionDetalle::class,
    CambioEvento::class,
    ClienteActividad::class,
    ClienteNota::class,
    ClienteImagen::class,
]);

it('relaciona una cita con cliente, consultorio, especialidades y servicios', function () {
    $event = Event::factory()->create();

    $especialidad = Especialidad::factory()->create();
    $servicio     = ServicioEspecialidad::factory()->for($especialidad)->create();

    $event->especialidades()->sync([$especialidad->id]);
    $event->servicios()->sync([$servicio->id]);

    expect($event->cliente)->toBeInstanceOf(Cliente::class)
        ->and($event->consultorio)->toBeInstanceOf(Consultorio::class)
        ->and($event->especialidades)->toHaveCount(1)
        ->and($event->servicios)->toHaveCount(1);
});

it('arma el expediente completo del cliente', function () {
    $cliente = Cliente::factory()
        ->has(ClienteActividad::factory()->count(2), 'actividades')
        ->has(ClienteNota::factory()->count(2), 'notas')
        ->has(ClienteImagen::factory(), 'imagenes')
        ->has(Evaluacion::factory(), 'evaluaciones')
        ->create();

    expect($cliente->actividades)->toHaveCount(2)
        ->and($cliente->notas)->toHaveCount(2)
        ->and($cliente->imagenes)->toHaveCount(1)
        ->and($cliente->evaluaciones)->toHaveCount(1);
});

it('no permite dos detalles para la misma pieza en una evaluación', function () {
    $detalle = EvaluacionDetalle::factory()->create(['pieza' => '11']);

    expect(fn () => EvaluacionDetalle::factory()->create([
        'evaluacion_id' => $detalle->evaluacion_id,
        'pieza'         => '11',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('los estados de cita del factory coinciden con el enum de la BD', function (string $estado, string $state) {
    $event = Event::factory()->{$state}()->create();

    expect($event->estado)->toBe($estado)
        ->and(Event::where('id', $event->id)->where('estado', $estado)->exists())->toBeTrue();
})->with([
    ['Pendiente', 'pendiente'],
    ['Confirmado', 'confirmado'],
    ['Cancelado', 'cancelado'],
    ['Reagendado', 'reagendado'],
    ['Se Presentó', 'sePresento'],
]);
