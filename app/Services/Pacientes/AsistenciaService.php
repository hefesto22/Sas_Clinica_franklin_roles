<?php

namespace App\Services\Pacientes;

use App\Models\ClienteActividad;
use App\Models\ClienteNota;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Registra la asistencia de un paciente a su cita.
 *
 * Extraído de la acción "Se Presentó" del CalendarWidget (Fase de deuda
 * técnica): registra la actividad en el expediente con su pago, gestiona
 * las notas y elimina la cita atendida — todo o nada.
 */
class AsistenciaService
{
    /**
     * @param  Event  $cita  Cita confirmada que se atendió
     * @param  string  $actividad  Servicio/actividad realizada
     * @param  float|string|null  $pago  Monto cobrado (L)
     * @param  string|null  $nuevaNota  Nota opcional para el expediente
     * @param  array<int>  $notasLeidasIds  Notas a marcar como leídas
     */
    public function registrar(
        Event $cita,
        string $actividad,
        float|string|null $pago = null,
        ?string $nuevaNota = null,
        array $notasLeidasIds = [],
        ?int $usuarioId = null,
    ): void {
        DB::transaction(function () use ($cita, $actividad, $pago, $nuevaNota, $notasLeidasIds, $usuarioId) {
            // 1) Actividad en el expediente (queda el rastro clínico y el pago)
            ClienteActividad::create([
                'cliente_id' => $cita->cliente_id,
                'fecha'      => now(),
                'actividad'  => $actividad,
                'pago'       => $pago ?? 0,
            ]);

            // 2) Nota nueva (opcional)
            if (filled($nuevaNota)) {
                ClienteNota::create([
                    'cliente_id' => $cita->cliente_id,
                    'contenido'  => $nuevaNota,
                    'leida'      => false,
                    'created_by' => $usuarioId ?? auth()->id(),
                ]);
            }

            // 3) Marcar como leídas SOLO las seleccionadas, del mismo cliente
            if ($notasLeidasIds !== []) {
                ClienteNota::whereIn('id', $notasLeidasIds)
                    ->where('cliente_id', $cita->cliente_id)
                    ->update(['leida' => true, 'updated_at' => now()]);
            }

            // 4) La cita atendida se elimina (la actividad queda como registro)
            $cita->delete();
        });
    }
}
