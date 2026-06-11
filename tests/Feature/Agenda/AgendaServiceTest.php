<?php

use App\Exceptions\Agenda\ClienteConCitaActivaException;
use App\Exceptions\Agenda\HorarioOcupadoException;
use App\Models\Cliente;
use App\Models\Consultorio;
use App\Models\ConsultorioTurno;
use App\Models\Especialidad;
use App\Models\Event;
use App\Models\ServicioEspecialidad;
use App\Models\User;
use App\Services\Agenda\AgendaService;
use Illuminate\Support\Carbon;

/**
 * Reglas de negocio del agendamiento (AgendaService).
 * Fechas dinámicas: próximo lunes a partir de hoy, para que la suite
 * no caduque con el calendario.
 */
function proximoLunes(): Carbon
{
    return Carbon::now()->next(Carbon::MONDAY)->startOfDay();
}

function consultorioParaAgendar(array $turno = [], string $modoDefecto = 'horario'): Consultorio
{
    $consultorio = Consultorio::factory()->state(['modo_defecto' => $modoDefecto])->create();

    ConsultorioTurno::factory()->create(array_merge([
        'consultorio_id' => $consultorio->id,
        'dia_semana'     => 1, // lunes
        'hora_inicio'    => '08:00',
        'hora_fin'       => '12:00',
    ], $turno));

    return $consultorio;
}

function datosCita(Consultorio $consultorio, Cliente $cliente, Carbon $inicio, ?Carbon $fin = null): array
{
    return [
        'cliente_id'     => $cliente->id,
        'consultorio_id' => $consultorio->id,
        'start_at'       => $inicio,
        'end_at'         => $fin ?? $inicio->copy()->addMinutes(30),
        'created_by'     => User::factory()->create()->id,
    ];
}

it('agenda una cita válida con estado Pendiente por defecto', function () {
    $consultorio = consultorioParaAgendar(['modo' => 'horario', 'slot_minutos' => 30]);
    $cliente     = Cliente::factory()->create();
    $inicio      = proximoLunes()->setTime(8, 0);

    $event = app(AgendaService::class)->agendar(datosCita($consultorio, $cliente, $inicio));

    expect($event->estado)->toBe('Pendiente')
        ->and(Event::count())->toBe(1);
});

it('sincroniza especialidades y servicios al agendar', function () {
    $consultorio  = consultorioParaAgendar();
    $cliente      = Cliente::factory()->create();
    $especialidad = Especialidad::factory()->create();
    $servicio     = ServicioEspecialidad::factory()->for($especialidad)->create();

    $event = app(AgendaService::class)->agendar(
        datosCita($consultorio, $cliente, proximoLunes()->setTime(9, 0)),
        especialidades: [$especialidad->id],
        servicios: [$servicio->id],
    );

    expect($event->especialidades)->toHaveCount(1)
        ->and($event->servicios)->toHaveCount(1);
});

it('rechaza agendar sobre una franja ya ocupada en modo horario', function () {
    $consultorio = consultorioParaAgendar(['modo' => 'horario', 'slot_minutos' => 30]);
    $inicio      = proximoLunes()->setTime(8, 0);

    Event::factory()->for($consultorio)
        ->enFranja($inicio->toDateTimeString(), $inicio->copy()->addMinutes(30)->toDateTimeString())
        ->create();

    $otroCliente = Cliente::factory()->create();

    expect(fn () => app(AgendaService::class)->agendar(
        datosCita($consultorio, $otroCliente, $inicio)
    ))->toThrow(HorarioOcupadoException::class);
});

it('permite hasta cupos_por_hora citas en modo cupos y rechaza al exceder', function () {
    $consultorio = consultorioParaAgendar(
        ['modo' => 'cupos', 'slot_minutos' => null, 'cupos_por_hora' => 2],
        modoDefecto: 'cupos',
    );
    $inicio  = proximoLunes()->setTime(8, 0);
    $fin     = $inicio->copy()->addHour();
    $agenda  = app(AgendaService::class);

    $agenda->agendar(datosCita($consultorio, Cliente::factory()->create(), $inicio, $fin));
    $agenda->agendar(datosCita($consultorio, Cliente::factory()->create(), $inicio, $fin));

    expect(fn () => $agenda->agendar(
        datosCita($consultorio, Cliente::factory()->create(), $inicio, $fin)
    ))->toThrow(HorarioOcupadoException::class);
});

it('una cita cancelada no resta capacidad al agendar', function () {
    $consultorio = consultorioParaAgendar(['modo' => 'horario', 'slot_minutos' => 30]);
    $inicio      = proximoLunes()->setTime(8, 0);

    Event::factory()->for($consultorio)->cancelado()
        ->enFranja($inicio->toDateTimeString(), $inicio->copy()->addMinutes(30)->toDateTimeString())
        ->create();

    $event = app(AgendaService::class)->agendar(
        datosCita($consultorio, Cliente::factory()->create(), $inicio)
    );

    expect($event->exists)->toBeTrue();
});

it('elimina la cita cancelada cuando la nueva ocupa su lugar', function () {
    $consultorio = consultorioParaAgendar(['modo' => 'horario', 'slot_minutos' => 30]);
    $inicio      = proximoLunes()->setTime(8, 0);

    $cancelada = Event::factory()->for($consultorio)->cancelado()
        ->enFranja($inicio->toDateTimeString(), $inicio->copy()->addMinutes(30)->toDateTimeString())
        ->create();

    app(AgendaService::class)->agendar(
        datosCita($consultorio, Cliente::factory()->create(), $inicio),
        canceladoEventoId: $cancelada->id,
    );

    expect(Event::find($cancelada->id))->toBeNull();
});

it('rechaza agendar si el cliente ya tiene una cita activa dentro de 25 días', function () {
    $consultorio = consultorioParaAgendar();
    $cliente     = Cliente::factory()->create();

    // Cita existente activa dentro de la ventana de 25 días
    Event::factory()->for($cliente)->pendiente()
        ->enFranja(now()->addDays(5)->setTime(10, 0)->toDateTimeString())
        ->create();

    expect(fn () => app(AgendaService::class)->agendar(
        datosCita($consultorio, $cliente, proximoLunes()->setTime(8, 0))
    ))->toThrow(ClienteConCitaActivaException::class);
});

it('permite agendar si la cita previa del cliente está cancelada', function () {
    $consultorio = consultorioParaAgendar(['modo' => 'horario', 'slot_minutos' => 30]);
    $cliente     = Cliente::factory()->create();

    Event::factory()->for($cliente)->cancelado()
        ->enFranja(now()->addDays(5)->setTime(10, 0)->toDateTimeString())
        ->create();

    $event = app(AgendaService::class)->agendar(
        datosCita($consultorio, $cliente, proximoLunes()->setTime(8, 0))
    );

    expect($event->exists)->toBeTrue();
});

it('no deja datos a medias si la franja está ocupada (rollback)', function () {
    $consultorio = consultorioParaAgendar(['modo' => 'horario', 'slot_minutos' => 30]);
    $inicio      = proximoLunes()->setTime(8, 0);

    Event::factory()->for($consultorio)
        ->enFranja($inicio->toDateTimeString(), $inicio->copy()->addMinutes(30)->toDateTimeString())
        ->create();

    try {
        app(AgendaService::class)->agendar(
            datosCita($consultorio, Cliente::factory()->create(), $inicio),
        );
    } catch (HorarioOcupadoException) {
        // esperado
    }

    expect(Event::count())->toBe(1); // solo la original
});
