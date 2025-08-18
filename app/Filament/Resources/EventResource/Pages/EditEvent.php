<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use App\Models\Event;
use App\Helpers\HorarioHelper;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalcular por si el usuario cambió fecha/hora en el form
        if (!empty($data['consultorio_id']) && !empty($this->data['start_date']) && !empty($this->data['start_time'])) {
            [$start, $end] = HorarioHelper::calcularRango(
                (int) $data['consultorio_id'],
                (string) $this->data['start_date'],
                // Si el label viene como "08:00 AM — 8 cupos", nos quedamos con "08:00"
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
        $fecha = (string) ($this->data['start_date'] ?? Carbon::parse($data['start_at'])->toDateString());
        $hora  = (string) preg_replace('/^(\d{2}:\d{2}).*$/', '$1', (string) ($this->data['start_time'] ?? Carbon::parse($data['start_at'])->format('H:i')));

        $start = Carbon::parse($data['start_at']);
        $end   = Carbon::parse($data['end_at']);

        // Detectar modo del turno para esa fecha/hora
        $consultorio = \App\Models\Consultorio::with('turnos')->findOrFail($cid);
        $dia = HorarioHelper::dayOfWeek(($fecha));
        $turno = $consultorio->turnos()
            ->where('dia_semana', $dia)
            ->where('activo', true)
            ->where('hora_inicio', '<=', $hora)
            ->where('hora_fin',   '>',  $hora)
            ->first();
        $modo = $turno->modo ?? $consultorio->modo_defecto ?? 'horario';

        if ($modo === 'cupos') {
            // Validación por capacidad del slot (excluye el propio evento)
            $capacidad = HorarioHelper::capacidadSlot($cid, $fecha, $hora);

            $reservas = Event::query()
                ->where('consultorio_id', $cid)
                ->where('id', '!=', $this->record->getKey())
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_at', [$start, $end->copy()->subSecond()])
                        ->orWhereBetween('end_at',   [$start->copy()->addSecond(), $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_at', '<=', $start)
                                ->where('end_at',   '>=', $end);
                        });
                })
                ->count();

            if ($reservas >= $capacidad) {
                throw ValidationException::withMessages([
                    'start_time' => 'Este horario alcanzó el máximo de cupos.',
                ]);
            }
        } else {
            // Modo "horario": no permitir solapes (excluyendo el propio evento)
            $overlap = Event::query()
                ->where('consultorio_id', $cid)
                ->where('id', '!=', $this->record->getKey())
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

        $data['updated_by'] = Auth::id();
        return $data;
    }
}
