<?php

namespace App\Services\Agenda;

use App\Exceptions\Agenda\AgendaException;
use App\Exceptions\Agenda\ClienteConCitaActivaException;
use App\Exceptions\Agenda\HorarioOcupadoException;
use App\Models\CambioEvento;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta el agendamiento de citas.
 *
 * Toda creación de citas pasa por aquí: las reglas de negocio
 * (capacidad de la franja, regla de 25 días entre citas del mismo
 * cliente) se garantizan dentro de una transacción con lockForUpdate,
 * eliminando la doble reserva cuando dos recepcionistas agendan
 * la misma franja a la vez.
 */
class AgendaService
{
    /** Días mínimos entre citas activas del mismo cliente. */
    public const DIAS_MINIMOS_ENTRE_CITAS = 25;

    public function __construct(
        private readonly DisponibilidadService $disponibilidad,
    ) {}

    /**
     * Agenda una cita validando capacidad y regla de cliente.
     *
     * @param  array  $data  Atributos del Event (cliente_id, consultorio_id, start_at, end_at, ...)
     * @param  array<int>  $especialidades  IDs a sincronizar
     * @param  array<int>  $servicios  IDs a sincronizar
     * @param  int|null  $canceladoEventoId  Cita cancelada cuyo lugar se ocupa (se elimina)
     *
     * @throws ClienteConCitaActivaException Si el cliente ya tiene cita activa en los próximos 25 días.
     * @throws HorarioOcupadoException Si la franja ya no tiene capacidad disponible.
     */
    public function agendar(
        array $data,
        array $especialidades = [],
        array $servicios = [],
        ?int $canceladoEventoId = null,
    ): Event {
        $start = Carbon::parse($data['start_at']);
        $end   = Carbon::parse($data['end_at']);

        return DB::transaction(function () use ($data, $especialidades, $servicios, $canceladoEventoId, $start, $end) {
            $this->garantizarClienteSinCitaActiva((int) $data['cliente_id']);
            $this->garantizarCapacidadDisponible((int) $data['consultorio_id'], $start, $end);

            $event = Event::create([
                ...$data,
                'estado' => $data['estado'] ?? 'Pendiente',
            ]);

            if ($especialidades !== []) {
                $event->especialidades()->sync($especialidades);
            }

            if ($servicios !== []) {
                $event->servicios()->sync($servicios);
            }

            // Si esta cita ocupa el lugar de una cancelada, la cancelada se elimina
            if ($canceladoEventoId) {
                Event::where('id', $canceladoEventoId)
                    ->where('estado', 'Cancelado')
                    ->delete();
            }

            return $event;
        });
    }

    /** Transición simple: la cita queda Confirmada. */
    public function confirmar(Event $cita): void
    {
        $cita->update(['estado' => 'Confirmado']);
    }

    /** Transición simple: la cita queda Cancelada (libera su franja). */
    public function cancelar(Event $cita): void
    {
        $cita->update(['estado' => 'Cancelado']);
    }

    /**
     * Busca el próximo día (saltando domingos) donde la hora dada esté libre
     * en el consultorio. Solo las citas activas bloquean; lógica unificada
     * de las 3 copias que vivían en el CalendarWidget.
     *
     * @throws AgendaException Si no hay fecha libre en el próximo año (evita loop infinito).
     */
    public function buscarProximaFechaLibre(
        int $consultorioId,
        Carbon $desde,
        string $hora,
        ?int $excluirEventoId = null,
    ): Carbon {
        $fecha = $desde->copy()->addDay();

        for ($dias = 0; $dias < 365; $dias++, $fecha->addDay()) {
            if ($fecha->isSunday()) {
                continue;
            }

            $candidata = $fecha->copy()->setTimeFromTimeString($hora);

            $ocupado = Event::query()
                ->where('consultorio_id', $consultorioId)
                ->whereIn('estado', Event::ESTADOS_OCUPADOS)
                ->where('start_at', $candidata)
                ->when($excluirEventoId, fn ($q) => $q->where('id', '!=', $excluirEventoId))
                ->exists();

            if (! $ocupado) {
                return $candidata;
            }
        }

        throw new AgendaException(
            "No se encontró una fecha libre en los próximos 365 días para el consultorio {$consultorioId}."
        );
    }

    /**
     * Mueve la cita a la próxima fecha libre (misma hora, sin domingos),
     * conservando su duración real, y la marca Reagendado.
     */
    public function reagendarAlProximoDisponible(Event $cita): Event
    {
        return DB::transaction(function () use ($cita) {
            $inicio   = Carbon::parse($cita->start_at);
            $duracion = (int) $inicio->diffInMinutes(Carbon::parse($cita->end_at));

            $nuevaFecha = $this->buscarProximaFechaLibre(
                $cita->consultorio_id,
                $inicio,
                $inicio->format('H:i:s'),
                excluirEventoId: $cita->id,
            );

            $cita->update([
                'start_at' => $nuevaFecha,
                'end_at'   => $nuevaFecha->copy()->addMinutes($duracion),
                'estado'   => 'Reagendado',
            ]);

            return $cita->refresh();
        });
    }

    /**
     * El paciente no se presentó: crea una nueva cita Pendiente en la próxima
     * fecha libre (misma hora y duración, mismo doctor, mismas especialidades
     * y servicios) y elimina la original.
     */
    public function noSePresento(Event $cita): Event
    {
        return DB::transaction(function () use ($cita) {
            $inicio   = Carbon::parse($cita->start_at);
            $duracion = (int) $inicio->diffInMinutes(Carbon::parse($cita->end_at));

            $nuevaFecha = $this->buscarProximaFechaLibre(
                $cita->consultorio_id,
                $inicio,
                $inicio->format('H:i:s'),
                excluirEventoId: $cita->id,
            );

            $nueva = Event::create([
                'cliente_id'     => $cita->cliente_id,
                'consultorio_id' => $cita->consultorio_id,
                'doctor_id'      => $cita->doctor_id,
                'telefono'       => $cita->telefono,
                'start_at'       => $nuevaFecha,
                'end_at'         => $nuevaFecha->copy()->addMinutes($duracion),
                'estado'         => 'Pendiente',
                'created_by'     => auth()->id() ?? $cita->created_by,
            ]);

            $nueva->especialidades()->sync($cita->especialidades->pluck('id')->all());
            $nueva->servicios()->sync($cita->servicios->pluck('id')->all());

            $cita->delete();

            return $nueva;
        });
    }

    /**
     * Solicita intercambiar la franja de dos citas: ambas pasan a
     * "Reagendando" y queda registrada la solicitud (CambioEvento) que
     * el doctor aprueba o rechaza.
     *
     * @throws AgendaException Si la cita alternativa no existe o ya no está Pendiente.
     */
    public function solicitarIntercambio(Event $origen, int $destinoId): CambioEvento
    {
        return DB::transaction(function () use ($origen, $destinoId) {
            $destino = Event::query()
                ->whereKey($destinoId)
                ->where('estado', 'Pendiente')
                ->lockForUpdate()
                ->first();

            if (! $destino) {
                throw new AgendaException(
                    'La cita alternativa ya no está disponible para intercambio.'
                );
            }

            $origen->update(['estado' => 'Reagendando']);
            $destino->update(['estado' => 'Reagendando']);

            return CambioEvento::create([
                'evento_id_origen'  => $origen->id,
                'evento_id_destino' => $destino->id,
                'created_by'        => auth()->id(),
                'estado'            => 'pendiente',
            ]);
        });
    }

    /**
     * Una cita cancelada toma el horario de la cita actual; la actual se
     * mueve a la próxima fecha libre y queda Reagendada.
     *
     * @return Event La cita recuperada (antes cancelada) ya en su nuevo horario.
     *
     * @throws AgendaException Si la cita seleccionada ya no está Cancelada.
     */
    public function asignarCanceladaAlHorario(Event $actual, int $canceladaId): Event
    {
        return DB::transaction(function () use ($actual, $canceladaId) {
            $cancelada = Event::query()
                ->whereKey($canceladaId)
                ->where('estado', 'Cancelado')
                ->lockForUpdate()
                ->first();

            if (! $cancelada) {
                throw new AgendaException('La cita seleccionada ya no está en estado Cancelado.');
            }

            $slotStart = Carbon::parse($actual->start_at);
            $slotEnd   = Carbon::parse($actual->end_at);

            // La cita actual se mueve a la próxima fecha libre
            $this->reagendarAlProximoDisponible($actual);

            // La cancelada recupera el slot original
            $cancelada->update([
                'consultorio_id' => $actual->consultorio_id,
                'start_at'       => $slotStart,
                'end_at'         => $slotEnd,
                'estado'         => 'Pendiente',
            ]);

            return $cancelada->refresh();
        });
    }

    /**
     * Verifica que la franja tenga capacidad libre, serializando reservas
     * concurrentes con lockForUpdate: la segunda transacción espera a la
     * primera y ve su cita ya creada.
     *
     * Capacidad: 1 en modo horario; cupos_por_hora en modo cupos.
     *
     * @throws HorarioOcupadoException
     */
    private function garantizarCapacidadDisponible(int $consultorioId, Carbon $start, Carbon $end): void
    {
        $ocupadas = Event::query()
            ->where('consultorio_id', $consultorioId)
            ->whereIn('estado', Event::ESTADOS_OCUPADOS)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->lockForUpdate()
            ->count();

        $capacidad = $this->disponibilidad->capacidadSlot(
            $consultorioId,
            $start->toDateString(),
            $start->format('H:i'),
        );

        if ($ocupadas >= $capacidad) {
            throw new HorarioOcupadoException($consultorioId, $start->toDateTimeString());
        }
    }

    /**
     * Regla de negocio: un cliente no puede tener más de una cita activa
     * en los próximos 25 días (extraída del closure del CalendarWidget).
     *
     * @throws ClienteConCitaActivaException
     */
    private function garantizarClienteSinCitaActiva(int $clienteId, ?int $excluirEventoId = null): void
    {
        $existente = Event::query()
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', Event::ESTADOS_OCUPADOS)
            ->whereBetween('start_at', [
                now()->startOfDay(),
                now()->addDays(self::DIAS_MINIMOS_ENTRE_CITAS)->endOfDay(),
            ])
            ->when($excluirEventoId, fn ($q) => $q->where('id', '!=', $excluirEventoId))
            ->orderBy('start_at')
            ->first();

        if ($existente) {
            throw new ClienteConCitaActivaException(
                clienteId: $clienteId,
                citaExistenteEn: (string) $existente->start_at,
                estadoCitaExistente: $existente->estado,
            );
        }
    }
}
