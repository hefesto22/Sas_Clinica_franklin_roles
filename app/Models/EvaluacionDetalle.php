<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionDetalle extends Model
{
    protected $fillable = [
        'evaluacion_id',
        'pieza',
        'diagnostico',
        'hecho', // Nuevo campo para el checkbox
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class);
    }
}
