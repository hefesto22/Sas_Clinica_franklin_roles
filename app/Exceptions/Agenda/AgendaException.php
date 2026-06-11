<?php

namespace App\Exceptions\Agenda;

/**
 * Base de los errores de negocio de la agenda.
 * Permite al UI capturar cualquier regla de agendamiento violada
 * con un solo catch.
 */
class AgendaException extends \RuntimeException
{
}
