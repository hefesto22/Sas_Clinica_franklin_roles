<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * La auditoría es de SOLO lectura y nadie tiene acceso por defecto:
 * el super_admin entra porque su Gate::before (Shield) responde antes
 * de llegar aquí. Nadie puede crear, editar ni borrar registros de
 * auditoría desde la aplicación — ni siquiera el super_admin (el
 * Gate::before lo permitiría, pero el Resource no expone esas acciones).
 */
class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Activity $activity): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return false;
    }
}
