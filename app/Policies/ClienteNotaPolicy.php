<?php

namespace App\Policies;

use App\Models\ClienteNota;
use App\Models\User;

/** Notas clínicas del expediente: solo roles clínicos. */
class ClienteNotaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_cliente_nota');
    }

    public function view(User $user, ClienteNota $nota): bool
    {
        return $user->can('view_cliente_nota');
    }

    public function create(User $user): bool
    {
        return $user->can('create_cliente_nota');
    }

    public function update(User $user, ClienteNota $nota): bool
    {
        return $user->can('update_cliente_nota');
    }

    public function delete(User $user, ClienteNota $nota): bool
    {
        return $user->can('delete_cliente_nota');
    }
}
