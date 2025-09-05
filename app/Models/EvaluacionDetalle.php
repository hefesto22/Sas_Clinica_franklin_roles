<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionDetalle extends Model
{
    protected $fillable = [
        'evaluacion_id',
        'pieza',
        'diagnostico',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class);
    }
}
