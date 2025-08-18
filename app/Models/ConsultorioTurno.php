<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultorioTurno extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultorio_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'modo',
        'slot_minutos',
        'cupos_por_hora',
        'activo',
    ];

    /**
     * Relación: cada turno pertenece a un consultorio.
     */
    public function consultorio(): BelongsTo
    {
        return $this->belongsTo(Consultorio::class);
    }

    /**
     * Helper: nombre del día en texto (1 = Lunes, ... 7 = Domingo).
     */
    public function getDiaNombreAttribute(): string
    {
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        return $dias[$this->dia_semana] ?? 'Desconocido';
    }
}
