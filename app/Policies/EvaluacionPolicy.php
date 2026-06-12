<?php

namespace App\Policies;

use App\Models\Evaluacion;
use App\Models\User;

/**
 * Odontograma/evaluaciones: detalle clínico sensible.
 * Recepción NO tiene estos permisos (decisión de negocio 2026-06-11).
 */
class EvaluacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_evaluacion');
    }

    public function view(User $user, Evaluacion $evaluacion): bool
    {
        return $user->can('view_evaluacion');
    }

    public function create(User $user): bool
    {
        return $user->can('create_evaluacion');
    }

    public function update(User $user, Evaluacion $evaluacion): bool
    {
        return $user->can('update_evaluacion');
    }

    public function delete(User $user, Evaluacion $evaluacion): bool
    {
        return $user->can('delete_evaluacion');
    }
}
