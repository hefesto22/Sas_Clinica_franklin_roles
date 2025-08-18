<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use App\Models\Consultorio;
use App\Models\Event;

class HorarioHelper
{
    /**
     * Intervalos de 10 min (no usados en el form principal, los dejo por si los necesitas).
     * Clave y valor en 12h (p.ej. "08:10 AM").
     */
    public static function horasDiezMinutos(): array
    {
        $inicio = Carbon::createFromTime(6, 0, 0);  // 6:00 AM
        $fin    = Carbon::createFromTime(20, 0, 0); // 8:00 PM

        $horas = [];

        while ($inicio <= $fin) {
            $key = $inicio->format('h:i A'); // 12h → "08:10 AM"
            $horas[$key] = $key;
            $inicio->addMinutes(10);
        }

        return $horas;
    }

    /**
     * Horas enteras entre startHour y endHour.
     * Devuelve ['HH:MM' => 'h:mm AM'] (p.ej. ['06:00' => '6:00 AM']).
     */
    public static function horasEnteras(int $startHour = 6, int $endHour = 20): array
    {
        $horas = [];
        $t   = Carbon::createFromTime($startHour, 0, 0);
        $end = Carbon::createFromTime($endHour,   0, 0);

        while ($t <= $end) {
            $key = $t->format('H:i');   // 06:00
            $val = $t->format('g:i A'); // 6:00 AM
            $horas[$key] = $val;
            $t->addHour();
        }

        return $horas;
    }

    /** Convierte 'HH:MM' a 'h:MM AM/PM'. */
    public static function to12h(string $time): string
    {
        [$H, $m] = explode(':', $time);
        $H = (int) $H;
        $ampm = $H >= 12 ? 'PM' : 'AM';
        $h12  = $H % 12;
        if ($h12 === 0) $h12 = 12;
        return "{$h12}:{$m} {$ampm}";
    }

    /** Carbon 0..6 → 1..7 (Lunes..Domingo) como usa tu BD. */
    // Antes:
    // public static function dayOfWeek(Carbon $date): int

    // Después (acepta string|DateTime|Carbon y lo normaliza):
    public static function dayOfWeek($date): int
    {
        // Normaliza a Illuminate\Support\Carbon
        if (! $date instanceof \Illuminate\Support\Carbon) {
            $date = \Illuminate\Support\Carbon::parse($date);
        }

        // 1..7 (Lunes..Domingo)
        return (int) $date->dayOfWeekIso; // L=1 ... D=7
    }


    /**
     * Opciones de horas disponibles por consultorio y fecha.
     * - Modo "cupos": 'h:mm AM — X cupos'
     * - Modo "horario": intervalos según slot_minutos
     *
     * @return array ['HH:MM' => 'label']
     */
    public static function opcionesDisponibles(?int $consultorioId, ?string $fechaYmd): array
    {
        if (!$consultorioId || !$fechaYmd) return [];

        /** @var Consultorio|null $consultorio */
        $consultorio = Consultorio::query()->with('turnos')->find($consultorioId);
        if (!$consultorio) return [];

        // ✅ Fuerza solo la parte de fecha
        $fecha = \Illuminate\Support\Carbon::parse($fechaYmd)->toDateString();
        $date  = \Illuminate\Support\Carbon::parse($fecha)->startOfDay();
        $diaSemana = self::dayOfWeek($date);

        $turnos = $consultorio->turnos()
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->get();

        if ($turnos->isEmpty()) {
            return [];
        }

        $opciones = [];

        foreach ($turnos as $turno) {
            // ✅ Normaliza hora a HH:MM (si viene con segundos se corta)
            $hi = substr((string)$turno->hora_inicio, 0, 5); // "08:00"
            $hf = substr((string)$turno->hora_fin,     0, 5); // "10:00"

            $inicio = \Illuminate\Support\Carbon::parse("$fecha $hi");
            $fin    = \Illuminate\Support\Carbon::parse("$fecha $hf");
            $modo   = $turno->modo ?? $consultorio->modo_defecto ?? 'horario';

            if ($modo === 'cupos') {
                $t = $inicio->copy();
                while ($t < $fin) {
                    $key       = $t->format('H:i');   // "08:00"
                    $labelBase = $t->format('g:i A'); // "8:00 AM"

                    $capacidad = self::capacidadSlot($consultorio->id, $fecha, $key);
                    $reservas  = self::reservasEnSlot($consultorio->id, $fecha, $key);
                    $disp      = max($capacidad - $reservas, 0);

                    if ($disp > 0) {
                        $opciones[$key] = "{$labelBase} — {$disp} cupos";
                    }

                    $t->addHour(); // cada hora
                }
            } else {
                $dur = (int) ($turno->slot_minutos ?: 30);
                $t = $inicio->copy();

                while ($t->lt($fin)) {
                    $slotStart = $t->copy();
                    $slotEnd   = $t->copy()->addMinutes($dur);
                    if ($slotEnd->gt($fin)) break;

                    $haySolape = Event::query()
                        ->where('consultorio_id', $consultorio->id)
                        ->where(function ($q) use ($slotStart, $slotEnd) {
                            $q->whereBetween('start_at', [$slotStart, $slotEnd->copy()->subSecond()])
                                ->orWhereBetween('end_at',   [$slotStart->copy()->addSecond(), $slotEnd])
                                ->orWhere(function ($q2) use ($slotStart, $slotEnd) {
                                    $q2->where('start_at', '<', $slotStart)
                                        ->where('end_at',   '>', $slotEnd);
                                });
                        })
                        ->exists();

                    if (!$haySolape) {
                        $key = $slotStart->format('H:i');
                        $opciones[$key] = $slotStart->format('g:i A');
                    }

                    $t->addMinutes($dur);
                }
            }
        }

        ksort($opciones);
        return $opciones;
    }


    /**
     * Calcula start_at y end_at según el modo y la hora elegida (HH:MM).
     * - Cupos: bloque de 60 min.
     * - Horario: usa slot_minutos del turno que cubra ese inicio.
     *
     * @return array{0:Carbon,1:Carbon}
     */
    public static function calcularRango(int $consultorioId, string $fechaYmd, string $hhmm): array
    {
        $consultorio = Consultorio::query()->with('turnos')->findOrFail($consultorioId);

        // ✅ solo fecha
        $fecha = \Illuminate\Support\Carbon::parse($fechaYmd)->toDateString();
        // ✅ hora HH:MM
        $hhmm  = substr($hhmm, 0, 5);

        $date  = \Illuminate\Support\Carbon::parse($fecha);
        $start = \Illuminate\Support\Carbon::parse("$fecha $hhmm");
        $diaSemana = self::dayOfWeek($date);

        $turno = $consultorio->turnos()
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->where('hora_inicio', '<=', $hhmm)
            ->where('hora_fin',   '>',  $hhmm)
            ->first();

        $modo = $turno->modo ?? $consultorio->modo_defecto ?? 'horario';
        $end  = $modo === 'cupos'
            ? $start->copy()->addHour()
            : $start->copy()->addMinutes((int) ($turno->slot_minutos ?: 30));

        return [$start, $end];
    }

    // HorarioHelper.php

    public static function capacidadSlot(int $consultorioId, string $fechaYmd, string $hhmm): int
    {
        $consultorio = Consultorio::query()->with('turnos')->findOrFail($consultorioId);

        $fecha = \Illuminate\Support\Carbon::parse($fechaYmd)->toDateString();
        $hhmm  = substr($hhmm, 0, 5);
        $dia   = self::dayOfWeek(\Illuminate\Support\Carbon::parse($fecha));

        $turno = $consultorio->turnos()
            ->where('dia_semana', $dia)
            ->where('activo', true)
            ->where('hora_inicio', '<=', $hhmm)
            ->where('hora_fin',   '>',  $hhmm)
            ->first();

        if (!$turno) return 1;

        return ($turno->modo ?? $consultorio->modo_defecto ?? 'horario') === 'cupos'
            ? (int) ($turno->cupos_por_hora ?: 1)
            : 1;
    }

    /** Cuenta reservas solapadas con el slot que empieza en $hhmm */
    public static function reservasEnSlot(int $consultorioId, string $fechaYmd, string $hhmm): int
    {
        [$start, $end] = self::calcularRango($consultorioId, $fechaYmd, $hhmm);

        return Event::query()
            ->where('consultorio_id', $consultorioId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end->copy()->subSecond()])
                    ->orWhereBetween('end_at',   [$start->copy()->addSecond(), $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_at', '<=', $start)
                            ->where('end_at',   '>=', $end);
                    });
            })
            ->count();
    }
}
