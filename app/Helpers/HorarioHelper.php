<?php

namespace App\Helpers;

use App\Services\Agenda\DisponibilidadService;
use Illuminate\Support\Carbon;

/**
 * Helpers de formato de horas + fachada estática hacia DisponibilidadService.
 *
 * La lógica de disponibilidad vive en App\Services\Agenda\DisponibilidadService
 * (Fase 2 del plan de mejora). Los métodos de abajo delegan para no romper
 * los llamados existentes del CalendarWidget; el código nuevo debe inyectar
 * el Service directamente.
 */
class HorarioHelper
{
    /**
     * Intervalos de 10 min en 12h (p.ej. "08:10 AM").
     */
    public static function horasDiezMinutos(): array
    {
        $inicio = Carbon::createFromTime(6, 0, 0);  // 6:00 AM
        $fin    = Carbon::createFromTime(20, 0, 0); // 8:00 PM

        $horas = [];

        while ($inicio <= $fin) {
            $key = $inicio->format('h:i A');
            $horas[$key] = $key;
            $inicio->addMinutes(10);
        }

        return $horas;
    }

    /**
     * Horas enteras entre startHour y endHour: ['HH:MM' => 'h:mm AM'].
     */
    public static function horasEnteras(int $startHour = 6, int $endHour = 20): array
    {
        $horas = [];
        $t   = Carbon::createFromTime($startHour, 0, 0);
        $end = Carbon::createFromTime($endHour, 0, 0);

        while ($t <= $end) {
            $horas[$t->format('H:i')] = $t->format('g:i A');
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
        if ($h12 === 0) {
            $h12 = 12;
        }

        return "{$h12}:{$m} {$ampm}";
    }

    /** Día de la semana ISO: 1=Lunes .. 7=Domingo (como usa la BD). */
    public static function dayOfWeek($date): int
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return (int) $date->dayOfWeekIso;
    }

    /* ──────── delegación a DisponibilidadService (compatibilidad) ──────── */

    /** @see DisponibilidadService::opcionesDisponibles() */
    public static function opcionesDisponibles(?int $consultorioId, ?string $fechaYmd): array
    {
        return app(DisponibilidadService::class)->opcionesDisponibles($consultorioId, $fechaYmd);
    }

    /** @see DisponibilidadService::calcularRango() */
    public static function calcularRango(int $consultorioId, string $fechaYmd, string $hhmm): array
    {
        return app(DisponibilidadService::class)->calcularRango($consultorioId, $fechaYmd, $hhmm);
    }

    /** @see DisponibilidadService::capacidadSlot() */
    public static function capacidadSlot(int $consultorioId, string $fechaYmd, string $hhmm): int
    {
        return app(DisponibilidadService::class)->capacidadSlot($consultorioId, $fechaYmd, $hhmm);
    }

    /** @see DisponibilidadService::reservasEnSlot() */
    public static function reservasEnSlot(int $consultorioId, string $fechaYmd, string $hhmm): int
    {
        return app(DisponibilidadService::class)->reservasEnSlot($consultorioId, $fechaYmd, $hhmm);
    }
}
