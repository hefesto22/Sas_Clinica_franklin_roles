<?php

namespace App\Policies;

use App\Models\ClienteImagen;
use App\Models\User;

/** Imágenes clínicas (radiografías, fotos): solo roles clínicos. */
class ClienteImagenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_cliente_imagen');
    }

    public function view(User $user, ClienteImagen $imagen): bool
    {
        return $user->can('view_cliente_imagen');
    }

    public function create(User $user): bool
    {
        return $user->can('create_cliente_imagen');
    }

    public function update(User $user, ClienteImagen $imagen): bool
    {
        return $user->can('update_cliente_imagen');
    }

    public function delete(User $user, ClienteImagen $imagen): bool
    {
        return $user->can('delete_cliente_imagen');
    }
}
