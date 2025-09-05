<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    // 👇 fuerza el nombre correcto de la tabla
    protected $table = 'evaluaciones';

    protected $fillable = [
        'cliente_id',
        'fecha',
        'limpieza_periodontal',
        'fluor',
        'observaciones',
        'user_id',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function detalles()
    {
        return $this->hasMany(EvaluacionDetalle::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
