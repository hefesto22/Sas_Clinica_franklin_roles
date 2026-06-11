<?php

use App\Helpers\HorarioHelper;
use App\Models\Consultorio;
use App\Models\ConsultorioTurno;
use App\Models\Event;

/**
 * Reglas de disponibilidad de la agenda (HorarioHelper).
 *
 * Fecha fija de referencia: lunes 2026-06-15 (dia_semana = 1).
 */
const LUNES = '2026-06-15';

function consultorioConTurno(array $turno = [], string $modoDefecto = 'horario'): Consultorio
{
    $consultorio = Consultorio::factory()->state(['modo_defecto' => $modoDefecto])->create();

    ConsultorioTurno::factory()->create(array_merge([
        'consultorio_id' => $consultorio->id,
        'dia_semana'     => 1, // lunes
        'hora_inicio'    => '08:00',
        'hora_fin'       => '10:00',
    ], $turno));

    return $consultorio;
}

it('genera los slots del turno según slot_minutos en modo horario', function () {
    $consultorio = consultorioConTurno(['modo' => 'horario', 'slot_minutos' => 30]);

    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);

    expect(array_keys($opciones))->toBe(['08:00', '08:30', '09:00', '09:30']);
});

it('excluye el slot ocupado por una cita existente en modo horario', function () {
    $consultorio = consultorioConTurno(['modo' => 'horario', 'slot_minutos' => 30]);

    Event::factory()
        ->for($consultorio)
        ->enFranja(LUNES . ' 08:00', LUNES . ' 08:30')
        ->create();

    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);

    expect($opciones)->not->toHaveKey('08:00')
        ->and(array_keys($opciones))->toBe(['08:30', '09:00', '09:30']);
});

it('no ofrece horarios un día sin turno configurado', function () {
    $consultorio = consultorioConTurno(); // solo lunes

    expect(HorarioHelper::opcionesDisponibles($consultorio->id, '2026-06-16'))->toBe([]); // martes
});

it('no ofrece horarios de un turno inactivo', function () {
    $consultorio = consultorioConTurno(['activo' => false]);

    expect(HorarioHelper::opcionesDisponibles($consultorio->id, LUNES))->toBe([]);
});

it('retorna vacío para consultorio o fecha inexistentes', function () {
    expect(HorarioHelper::opcionesDisponibles(null, LUNES))->toBe([])
        ->and(HorarioHelper::opcionesDisponibles(999999, LUNES))->toBe([]);
});

it('muestra los cupos restantes por hora en modo cupos', function () {
    $consultorio = consultorioConTurno(
        ['modo' => 'cupos', 'slot_minutos' => null, 'cupos_por_hora' => 2],
        modoDefecto: 'cupos',
    );

    // Sin reservas: 2 cupos en cada hora
    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);
    expect($opciones['08:00'])->toContain('2 cupos')
        ->and($opciones['09:00'])->toContain('2 cupos');

    // Una reserva 08:00-09:00 → queda 1 cupo
    Event::factory()->for($consultorio)
        ->enFranja(LUNES . ' 08:00', LUNES . ' 09:00')->create();

    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);
    expect($opciones['08:00'])->toContain('1 cupos');
});

it('oculta la hora cuando se agotan los cupos', function () {
    $consultorio = consultorioConTurno(
        ['modo' => 'cupos', 'slot_minutos' => null, 'cupos_por_hora' => 2],
        modoDefecto: 'cupos',
    );

    Event::factory()->for($consultorio)->count(2)
        ->enFranja(LUNES . ' 08:00', LUNES . ' 09:00')->create();

    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);

    expect($opciones)->not->toHaveKey('08:00')
        ->and($opciones)->toHaveKey('09:00');
});

it('calcula el rango según slot_minutos en modo horario', function () {
    $consultorio = consultorioConTurno(['modo' => 'horario', 'slot_minutos' => 45]);

    [$start, $end] = HorarioHelper::calcularRango($consultorio->id, LUNES, '08:00');

    expect($start->format('Y-m-d H:i'))->toBe(LUNES . ' 08:00')
        ->and($end->format('Y-m-d H:i'))->toBe(LUNES . ' 08:45');
});

it('calcula bloques de una hora en modo cupos', function () {
    $consultorio = consultorioConTurno(
        ['modo' => 'cupos', 'slot_minutos' => null, 'cupos_por_hora' => 4],
        modoDefecto: 'cupos',
    );

    [$start, $end] = HorarioHelper::calcularRango($consultorio->id, LUNES, '09:00');

    expect($end->format('H:i'))->toBe('10:00');
});

it('capacidadSlot retorna los cupos del turno en modo cupos y 1 en modo horario', function () {
    $cupos = consultorioConTurno(
        ['modo' => 'cupos', 'slot_minutos' => null, 'cupos_por_hora' => 6],
        modoDefecto: 'cupos',
    );
    $horario = consultorioConTurno(['modo' => 'horario', 'slot_minutos' => 30]);

    expect(HorarioHelper::capacidadSlot($cupos->id, LUNES, '08:00'))->toBe(6)
        ->and(HorarioHelper::capacidadSlot($horario->id, LUNES, '08:00'))->toBe(1);
});

it('convierte el día de la semana a formato ISO 1=Lunes..7=Domingo', function () {
    expect(HorarioHelper::dayOfWeek('2026-06-15'))->toBe(1)  // lunes
        ->and(HorarioHelper::dayOfWeek('2026-06-21'))->toBe(7); // domingo
});

/**
 * Fix autorizado en Fase 2: solo los estados activos (Event::ESTADOS_OCUPADOS)
 * bloquean franjas. Una cita cancelada libera su horario.
 */
it('una cita cancelada no bloquea el slot', function () {
    $consultorio = consultorioConTurno(['modo' => 'horario', 'slot_minutos' => 30]);

    Event::factory()->for($consultorio)->cancelado()
        ->enFranja(LUNES . ' 08:00', LUNES . ' 08:30')->create();

    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);

    expect($opciones)->toHaveKey('08:00');
});

it('una cita cancelada no consume cupos', function () {
    $consultorio = consultorioConTurno(
        ['modo' => 'cupos', 'slot_minutos' => null, 'cupos_por_hora' => 2],
        modoDefecto: 'cupos',
    );

    Event::factory()->for($consultorio)->cancelado()
        ->enFranja(LUNES . ' 08:00', LUNES . ' 09:00')->create();

    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);

    expect($opciones['08:00'])->toContain('2 cupos');
});

it('una cita confirmada sí bloquea el slot', function () {
    $consultorio = consultorioConTurno(['modo' => 'horario', 'slot_minutos' => 30]);

    Event::factory()->for($consultorio)->confirmado()
        ->enFranja(LUNES . ' 08:00', LUNES . ' 08:30')->create();

    $opciones = HorarioHelper::opcionesDisponibles($consultorio->id, LUNES);

    expect($opciones)->not->toHaveKey('08:00');
});
