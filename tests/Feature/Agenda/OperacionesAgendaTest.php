<?php

use App\Exceptions\Agenda\AgendaException;
use App\Models\CambioEvento;
use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Event;
use App\Models\ServicioEspecialidad;
use App\Models\User;
use App\Services\Agenda\AgendaService;
use Illuminate\Support\Carbon;

/**
 * Operaciones de agenda extraídas del CalendarWidget en Fase 2:
 * reagendar, no se presentó, intercambio y recuperación de canceladas.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function proximoSabado(): Carbon
{
    return Carbon::now()->next(Carbon::SATURDAY)->startOfDay();
}

function agendaService(): AgendaService
{
    return app(AgendaService::class);
}

it('busca la próxima fecha libre saltando domingos', function () {
    $consultorio = Consultorio::factory()->create();
    $sabado      = proximoSabado()->setTime(8, 0);

    $fecha = agendaService()->buscarProximaFechaLibre($consultorio->id, $sabado, '08:00:00');

    expect($fecha->isMonday())->toBeTrue()
        ->and($fecha->format('H:i'))->toBe('08:00');
});

it('salta los días cuya hora ya está ocupada, ignorando canceladas', function () {
    $consultorio = Consultorio::factory()->create();
    $sabado      = proximoSabado()->setTime(8, 0);
    $lunes       = $sabado->copy()->next(Carbon::MONDAY)->setTime(8, 0);
    $martes      = $lunes->copy()->addDay();

    // Lunes ocupado por cita activa; martes solo tiene una cancelada
    Event::factory()->for($consultorio)->pendiente()
        ->enFranja($lunes->toDateTimeString())->create();
    Event::factory()->for($consultorio)->cancelado()
        ->enFranja($martes->toDateTimeString())->create();

    $fecha = agendaService()->buscarProximaFechaLibre($consultorio->id, $sabado, '08:00:00');

    expect($fecha->toDateTimeString())->toBe($martes->toDateTimeString());
});

it('reagenda al próximo disponible conservando la duración real', function () {
    $consultorio = Consultorio::factory()->create();
    $inicio      = proximoSabado()->setTime(9, 0);

    $cita = Event::factory()->for($consultorio)->pendiente()
        ->enFranja($inicio->toDateTimeString(), $inicio->copy()->addMinutes(45)->toDateTimeString())
        ->create();

    $cita = agendaService()->reagendarAlProximoDisponible($cita);

    $nuevoInicio = Carbon::parse($cita->start_at);

    expect($cita->estado)->toBe('Reagendado')
        ->and($nuevoInicio->isMonday())->toBeTrue()
        ->and($nuevoInicio->diffInMinutes(Carbon::parse($cita->end_at)))->toEqual(45.0);
});

it('al no presentarse crea una nueva cita Pendiente equivalente y elimina la original', function () {
    $consultorio  = Consultorio::factory()->create();
    $doctor       = User::factory()->create();
    $especialidad = Especialidad::factory()->create();
    $servicio     = ServicioEspecialidad::factory()->for($especialidad)->create();
    $inicio       = proximoSabado()->setTime(10, 0);

    $cita = Event::factory()->for($consultorio)->confirmado()->conDoctor($doctor)
        ->enFranja($inicio->toDateTimeString())
        ->create();
    $cita->especialidades()->sync([$especialidad->id]);
    $cita->servicios()->sync([$servicio->id]);

    $nueva = agendaService()->noSePresento($cita);

    expect(Event::find($cita->id))->toBeNull()
        ->and($nueva->estado)->toBe('Pendiente')
        ->and($nueva->doctor_id)->toBe($doctor->id)
        ->and($nueva->cliente_id)->toBe($cita->cliente_id)
        ->and($nueva->especialidades)->toHaveCount(1)
        ->and($nueva->servicios)->toHaveCount(1)
        ->and(Carbon::parse($nueva->start_at)->isMonday())->toBeTrue();
});

it('solicita intercambio: ambas citas quedan Reagendando con la solicitud pendiente', function () {
    $origen  = Event::factory()->pendiente()->create();
    $destino = Event::factory()->pendiente()->create();

    $solicitud = agendaService()->solicitarIntercambio($origen, $destino->id);

    expect($origen->refresh()->estado)->toBe('Reagendando')
        ->and($destino->refresh()->estado)->toBe('Reagendando')
        ->and($solicitud->estado)->toBe('pendiente');
});

it('rechaza el intercambio si la cita alternativa ya no está Pendiente', function () {
    $origen  = Event::factory()->pendiente()->create();
    $destino = Event::factory()->confirmado()->create();

    expect(fn () => agendaService()->solicitarIntercambio($origen, $destino->id))
        ->toThrow(AgendaException::class);
});

it('al aceptar el intercambio se intercambian las fechas y se elimina la solicitud', function () {
    $inicioA = proximoSabado()->setTime(8, 0);
    $inicioB = proximoSabado()->addDays(2)->setTime(10, 0);

    $origen  = Event::factory()->pendiente()->enFranja($inicioA->toDateTimeString())->create();
    $destino = Event::factory()->pendiente()->enFranja($inicioB->toDateTimeString())->create();

    $solicitud = agendaService()->solicitarIntercambio($origen, $destino->id);

    // El doctor acepta → CambioEventoObserver hace el intercambio
    $solicitud->update(['estado' => 'aceptado']);

    expect(Carbon::parse($origen->refresh()->start_at)->toDateTimeString())->toBe($inicioB->toDateTimeString())
        ->and($origen->estado)->toBe('Reagendado')
        ->and(Carbon::parse($destino->refresh()->start_at)->toDateTimeString())->toBe($inicioA->toDateTimeString())
        ->and($destino->estado)->toBe('Confirmado')
        ->and(CambioEvento::find($solicitud->id))->toBeNull();
});

it('al rechazar el intercambio ambas citas vuelven a Pendiente', function () {
    $origen  = Event::factory()->pendiente()->create();
    $destino = Event::factory()->pendiente()->create();

    $solicitud = agendaService()->solicitarIntercambio($origen, $destino->id);
    $solicitud->update(['estado' => 'rechazado']);

    expect($origen->refresh()->estado)->toBe('Pendiente')
        ->and($destino->refresh()->estado)->toBe('Pendiente')
        ->and(CambioEvento::find($solicitud->id))->toBeNull();
});

it('una cancelada recupera el horario de la cita actual y la actual se reagenda', function () {
    $consultorio = Consultorio::factory()->create();
    $slot        = proximoSabado()->setTime(8, 0);

    $actual = Event::factory()->for($consultorio)->pendiente()
        ->enFranja($slot->toDateTimeString())->create();

    $cancelada = Event::factory()->for($consultorio)->cancelado()
        ->enFranja($slot->copy()->subDays(3)->toDateTimeString())->create();

    $recuperada = agendaService()->asignarCanceladaAlHorario($actual, $cancelada->id);

    expect($recuperada->estado)->toBe('Pendiente')
        ->and(Carbon::parse($recuperada->start_at)->toDateTimeString())->toBe($slot->toDateTimeString())
        ->and($actual->refresh()->estado)->toBe('Reagendado')
        ->and(Carbon::parse($actual->start_at)->toDateTimeString())->not->toBe($slot->toDateTimeString());
});

it('rechaza asignar una cita que ya no está Cancelada', function () {
    $actual    = Event::factory()->pendiente()->create();
    $noCancelada = Event::factory()->confirmado()->create();

    expect(fn () => agendaService()->asignarCanceladaAlHorario($actual, $noCancelada->id))
        ->toThrow(AgendaException::class);
});

it('confirmar y cancelar cambian el estado de la cita', function () {
    $cita = Event::factory()->pendiente()->create();

    agendaService()->confirmar($cita);
    expect($cita->refresh()->estado)->toBe('Confirmado');

    agendaService()->cancelar($cita);
    expect($cita->refresh()->estado)->toBe('Cancelado');
});
