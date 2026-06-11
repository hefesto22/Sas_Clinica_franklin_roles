<?php

namespace App\Exceptions\Agenda;

class HorarioOcupadoException extends AgendaException
{
    public function __construct(
        public readonly int $consultorioId,
        public readonly string $inicio,
    ) {
        parent::__construct(
            "El horario {$inicio} ya no está disponible en este consultorio. Elige otra franja."
        );
    }
}
