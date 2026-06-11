<?php

namespace App\Services\Agenda;

use App\Models\Consultorio;
use App\Models\ConsultorioTurno;
use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * Disponibilidad de la agenda por consultorio y fecha.
 *
 * Lógica extraída de HorarioHelper (Fase 2 del plan de mejora).
 * A diferencia del helper original, SOLO las citas en estados activos
 * (Event::ESTADOS_OCUPADOS) bloquean franjas: una cita cancelada
 * libera su horario.
 */
class DisponibilidadService
{
    /**
     * Opciones de horas disponibles por consultorio y fecha.
     * - Modo "cupos":  'h:mm AM — X cupos' por hora con cupo libre.
     * - Modo "horario": intervalos según slot_minutos sin solape activo.
     *
     * @return array<string, string> ['HH:MM' => 'label']
     */
    public function opcionesDisponibles(?int $consultorioId, ?string $fechaYmd): array
    {
        if (! $consultorioId || ! $fechaYmd) {
            return [];
        }

        $consultorio = Consultorio::query()->find($consultorioId);

        if (! $consultorio) {
            return [];
        }

        $fecha  = Carbon::parse($fechaYmd)->toDateString();
        $turnos = $this->turnosActivosDelDia($consultorio, $fecha);

        $opciones = [];

        foreach ($turnos as $turno) {
            $inicio = Carbon::parse("$fecha " . $this->normalizarHora($turno->hora_inicio));
            $fin    = Carbon::parse("$fecha " . $this->normalizarHora($turno->hora_fin));
            $modo   = $turno->modo ?? $consultorio->modo_defecto ?? 'horario';

            if ($modo === 'cupos') {
                $opciones += $this->opcionesPorCupos($consultorio, $fecha, $inicio, $fin);
            } else {
                $opciones += $this->opcionesPorHorario($consultorio, $turno, $inicio, $fin);
            }
        }

        ksort($opciones);

        return $opciones;
    }

    /**
     * Calcula start_at y end_at según el modo y la hora elegida (HH:MM).
     * - Cupos: bloque de 60 min. - Horario: slot_minutos del turno.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function calcularRango(int $consultorioId, string $fechaYmd, string $hhmm): array
    {
        $consultorio = Consultorio::query()->findOrFail($consultorioId);

        $fecha = Carbon::parse($fechaYmd)->toDateString();
        $hhmm  = substr($hhmm, 0, 5);
        $start = Carbon::parse("$fecha $hhmm");

        $turno = $this->turnoQueCubre($consultorio, $fecha, $hhmm);
        $modo  = $turno->modo ?? $consultorio->modo_defecto ?? 'horario';

        $end = $modo === 'cupos'
            ? $start->copy()->addHour()
            : $start->copy()->addMinutes((int) ($turno->slot_minutos ?: 30));

        return [$start, $end];
    }

    /** Capacidad del slot: cupos_por_hora en modo cupos, 1 en modo horario. */
    public function capacidadSlot(int $consultorioId, string $fechaYmd, string $hhmm): int
    {
        $consultorio = Consultorio::query()->findOrFail($consultorioId);

        $fecha = Carbon::parse($fechaYmd)->toDateString();
        $turno = $this->turnoQueCubre($consultorio, $fecha, substr($hhmm, 0, 5));

        if (! $turno) {
            return 1;
        }

        return ($turno->modo ?? $consultorio->modo_defecto ?? 'horario') === 'cupos'
            ? (int) ($turno->cupos_por_hora ?: 1)
            : 1;
    }

    /** Cuenta reservas activas solapadas con el slot que empieza en $hhmm. */
    public function reservasEnSlot(int $consultorioId, string $fechaYmd, string $hhmm): int
    {
        [$start, $end] = $this->calcularRango($consultorioId, $fechaYmd, $hhmm);

        return $this->queryReservasActivas($consultorioId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end->copy()->subSecond()])
                    ->orWhereBetween('end_at', [$start->copy()->addSecond(), $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_at', '<=', $start)
                            ->where('end_at', '>=', $end);
                    });
            })
            ->count();
    }

    /** ¿Hay alguna cita activa que solape el rango [start, end)? */
    public function haySolapeActivo(int $consultorioId, Carbon $start, Carbon $end): bool
    {
        return $this->queryReservasActivas($consultorioId)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();
    }

    /* ──────────────────────────── privados ──────────────────────────── */

    /** Base query de citas que ocupan agenda (excluye Cancelado y Se Presentó). */
    private function queryReservasActivas(int $consultorioId)
    {
        return Event::query()
            ->where('consultorio_id', $consultorioId)
            ->whereIn('estado', Event::ESTADOS_OCUPADOS);
    }

    private function opcionesPorCupos(Consultorio $consultorio, string $fecha, Carbon $inicio, Carbon $fin): array
    {
        $opciones = [];
        $t = $inicio->copy();

        while ($t < $fin) {
            $key       = $t->format('H:i');
            $capacidad = $this->capacidadSlot($consultorio->id, $fecha, $key);
            $reservas  = $this->reservasEnSlot($consultorio->id, $fecha, $key);
            $libres    = max($capacidad - $reservas, 0);

            if ($libres > 0) {
                $opciones[$key] = $t->format('g:i A') . " — {$libres} cupos";
            }

            $t->addHour();
        }

        return $opciones;
    }

    private function opcionesPorHorario(Consultorio $consultorio, ConsultorioTurno $turno, Carbon $inicio, Carbon $fin): array
    {
        $opciones = [];
        $duracion = (int) ($turno->slot_minutos ?: 30);
        $t = $inicio->copy();

        while ($t->lt($fin)) {
            $slotStart = $t->copy();
            $slotEnd   = $t->copy()->addMinutes($duracion);

            if ($slotEnd->gt($fin)) {
                break;
            }

            if (! $this->haySolapeActivo($consultorio->id, $slotStart, $slotEnd)) {
                $opciones[$slotStart->format('H:i')] = $slotStart->format('g:i A');
            }

            $t->addMinutes($duracion);
        }

        return $opciones;
    }

    private function turnosActivosDelDia(Consultorio $consultorio, string $fecha)
    {
        return $consultorio->turnos()
            ->where('dia_semana', Carbon::parse($fecha)->dayOfWeekIso)
            ->where('activo', true)
            ->get();
    }

    private function turnoQueCubre(Consultorio $consultorio, string $fecha, string $hhmm): ?ConsultorioTurno
    {
        return $consultorio->turnos()
            ->where('dia_semana', Carbon::parse($fecha)->dayOfWeekIso)
            ->where('activo', true)
            ->where('hora_inicio', '<=', $hhmm)
            ->where('hora_fin', '>', $hhmm)
            ->first();
    }

    /** Normaliza '08:00:00' → '08:00'. */
    private function normalizarHora(string $hora): string
    {
        return substr($hora, 0, 5);
    }
}
