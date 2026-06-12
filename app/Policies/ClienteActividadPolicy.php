<?php

namespace App\Policies;

use App\Models\ClienteActividad;
use App\Models\User;

/** Actividades/pagos del expediente: recepción sí las ve (cobranza). */
class ClienteActividadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_cliente_actividad');
    }

    public function view(User $user, ClienteActividad $actividad): bool
    {
        return $user->can('view_cliente_actividad');
    }

    public function create(User $user): bool
    {
        return $user->can('create_cliente_actividad');
    }

    public function update(User $user, ClienteActividad $actividad): bool
    {
        return $user->can('update_cliente_actividad');
    }

    public function delete(User $user, ClienteActividad $actividad): bool
    {
        return $user->can('delete_cliente_actividad');
    }
}
