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

it('eliminar un cliente lo ARCHIVA y conserva su historial de citas', function () {
    $cita    = Event::factory()->create();
    $cliente = Cliente::find($cita->cliente_id);

    $cliente->delete(); // soft delete

    expect(Cliente::find($cliente->id))->toBeNull()                      // fuera de las listas
        ->and(Cliente::withTrashed()->find($cliente->id))->not->toBeNull() // pero archivado
        ->and(Event::find($cita->id))->not->toBeNull();                   // historial intacto
});

it('un cliente archivado puede restaurarse', function () {
    $cliente = Cliente::factory()->create();
    $cliente->delete();

    Cliente::withTrashed()->find($cliente->id)->restore();

    expect(Cliente::find($cliente->id))->not->toBeNull();
});

it('protege el historial: el borrado FÍSICO de un cliente con citas es rechazado', function () {
    $cita = Event::factory()->create();

    expect(fn () => Cliente::find($cita->cliente_id)->forceDelete())
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('protege la agenda: no se puede borrar un consultorio con citas', function () {
    $cita = Event::factory()->create();

    expect(fn () => $cita->consultorio->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('una cita archivada libera su franja en la agenda', function () {
    $consultorio = \App\Models\Consultorio::factory()->create();
    \App\Models\ConsultorioTurno::factory()->create([
        'consultorio_id' => $consultorio->id,
        'dia_semana'     => 1,
        'hora_inicio'    => '08:00',
        'hora_fin'       => '10:00',
        'modo'           => 'horario',
        'slot_minutos'   => 30,
    ]);

    $lunes = \Illuminate\Support\Carbon::now()->next(\Illuminate\Support\Carbon::MONDAY)->setTime(8, 0);

    $cita = Event::factory()->for($consultorio)->pendiente()
        ->enFranja($lunes->toDateTimeString())->create();

    $disponibilidad = app(\App\Services\Agenda\DisponibilidadService::class);

    expect($disponibilidad->opcionesDisponibles($consultorio->id, $lunes->toDateString()))
        ->not->toHaveKey('08:00');

    $cita->delete(); // archivada → el scope la excluye de toda la agenda

    expect($disponibilidad->opcionesDisponibles($consultorio->id, $lunes->toDateString()))
        ->toHaveKey('08:00');
});
