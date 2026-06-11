<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Models\Consultorio;
use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use App\Models\Event;
use App\Helpers\HorarioHelper;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Recalcular start/end por si el form no hidrató
        if (!empty($data['consultorio_id']) && !empty($this->data['start_date']) && !empty($this->data['start_time'])) {
            [$start, $end] = HorarioHelper::calcularRango(
                (int) $data['consultorio_id'],
                (string) $this->data['start_date'],
                // si el label viene como "08:00 — 8 cupos", nos quedamos con "08:00"
                (string) preg_replace('/^(\d{2}:\d{2}).*$/', '$1', (string) $this->data['start_time']),
            );
            $data['start_at'] = $start->toDateTimeString();
            $data['end_at']   = $end->toDateTimeString();
        }

        if (empty($data['start_at']) || empty($data['end_at'])) {
            throw ValidationException::withMessages([
                'start_time' => 'Seleccione consultorio, fecha y hora válida.',
            ]);
        }

        $cid   = (int) $data['consultorio_id'];
        $fecha = (string) ($this->data['start_date'] ?? \Carbon\Carbon::parse($data['start_at'])->toDateString());
        $hora  = (string) preg_replace('/^(\d{2}:\d{2}).*$/', '$1', (string) ($this->data['start_time'] ?? \Carbon\Carbon::parse($data['start_at'])->format('H:i')));

        $start = \Carbon\Carbon::parse($data['start_at']);
        $end   = \Carbon\Carbon::parse($data['end_at']);

        // Detectar modo del turno para esa fecha/hora
        $consultorio = Consultorio::with('turnos')->findOrFail($cid);
        $dia = HorarioHelper::dayOfWeek(($fecha));
        $turno = $consultorio->turnos()
            ->where('dia_semana', $dia)
            ->where('activo', true)
            ->where('hora_inicio', '<=', $hora)
            ->where('hora_fin',   '>',  $hora)
            ->first();
        $modo = $turno->modo ?? $consultorio->modo_defecto ?? 'horario';

        if ($modo === 'cupos') {
            // Validación por capacidad del slot (permite varios hasta llenar)
            $capacidad = HorarioHelper::capacidadSlot($cid, $fecha, $hora);
            $reservas  = HorarioHelper::reservasEnSlot($cid, $fecha, $hora);

            if ($reservas >= $capacidad) {
                throw ValidationException::withMessages([
                    'start_time' => 'Este horario alcanzó el máximo de cupos.',
                ]);
            }
        } else {
            // Modo "horario": no permitir solapes
            $overlap = Event::query()
                ->where('consultorio_id', $cid)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_at', [$start, $end->copy()->subSecond()])
                        ->orWhereBetween('end_at',   [$start->copy()->addSecond(), $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_at', '<', $start)
                                ->where('end_at',   '>', $end);
                        });
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_time' => 'El horario seleccionado ya no está disponible.',
                ]);
            }
        }

        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['updated_by'] = Auth::id();

        return $data;
    }
}
