<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Auditoría de cambios para registros clínicos.
 *
 * Todo create/update/delete del modelo queda en la tabla activity_log
 * con el diff de atributos (solo los que cambiaron) y el usuario causante.
 * La tabla solo es visible para super_admin vía AuditoriaResource.
 */
trait RegistraAuditoria
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('clinico');
    }
}
