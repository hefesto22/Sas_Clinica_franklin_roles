<?php

use App\Models\Cliente;
use App\Models\ClienteActividad;
use App\Models\ClienteNota;
use App\Models\Event;
use App\Services\Pacientes\AsistenciaService;

it('registra la asistencia: actividad con pago, elimina la cita', function () {
    $cita = Event::factory()->confirmado()->create();

    app(AsistenciaService::class)->registrar(
        cita: $cita,
        actividad: 'Limpieza dental',
        pago: 800,
    );

    expect(Event::find($cita->id))->toBeNull()
        ->and(ClienteActividad::where('cliente_id', $cita->cliente_id)->count())->toBe(1)
        ->and((float) ClienteActividad::first()->pago)->toBe(800.0);
});

it('guarda la nota nueva y marca como leídas solo las seleccionadas', function () {
    $cliente = Cliente::factory()->create();
    $cita    = Event::factory()->confirmado()->for($cliente)->create();

    $notaSeleccionada = ClienteNota::factory()->for($cliente)->create();
    $notaNoSeleccionada = ClienteNota::factory()->for($cliente)->create();

    app(AsistenciaService::class)->registrar(
        cita: $cita,
        actividad: 'Consulta general',
        nuevaNota: 'Paciente sensible en pieza 24',
        notasLeidasIds: [$notaSeleccionada->id],
    );

    expect($notaSeleccionada->refresh()->leida)->toBeTrue()
        ->and($notaNoSeleccionada->refresh()->leida)->toBeFalse()
        ->and(ClienteNota::where('contenido', 'Paciente sensible en pieza 24')->exists())->toBeTrue();
});

it('no marca como leídas notas de otro cliente aunque se cuelen sus IDs', function () {
    $cita       = Event::factory()->confirmado()->create();
    $notaAjena  = ClienteNota::factory()->create(); // de otro cliente

    app(AsistenciaService::class)->registrar(
        cita: $cita,
        actividad: 'Consulta',
        notasLeidasIds: [$notaAjena->id],
    );

    expect($notaAjena->refresh()->leida)->toBeFalse();
});

it('protege el historial: no se puede borrar un cliente con citas', function () {
    $cita = Event::factory()->create();

    expect(fn () => Cliente::find($cita->cliente_id)->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('protege la agenda: no se puede borrar un consultorio con citas', function () {
    $cita = Event::factory()->create();

    expect(fn () => $cita->consultorio->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('protege el odontograma: no se puede borrar un cliente con evaluaciones', function () {
    $evaluacion = \App\Models\Evaluacion::factory()->create();

    expect(fn () => Cliente::find($evaluacion->cliente_id)->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);
});
