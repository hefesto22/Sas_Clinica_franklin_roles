<?php

namespace App\Exceptions\Agenda;

use Illuminate\Support\Carbon;

class ClienteConCitaActivaException extends AgendaException
{
    public function __construct(
        public readonly int $clienteId,
        public readonly string $citaExistenteEn,
        public readonly string $estadoCitaExistente,
    ) {
        $fecha = Carbon::parse($citaExistenteEn)->format('d/m/Y h:i A');

        parent::__construct(
            "Este cliente ya tiene una cita {$estadoCitaExistente} el {$fecha}. " .
            'No puede agendar otra como mínimo dentro de 25 días.'
        );
    }
}
