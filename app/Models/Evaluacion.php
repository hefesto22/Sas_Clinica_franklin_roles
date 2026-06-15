<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    use HasFactory;
    use \App\Models\Concerns\RegistraAuditoria;
    use \Illuminate\Database\Eloquent\SoftDeletes; // el odontograma se archiva, no se borra

    // 👇 fuerza el nombre correcto de la tabla
    protected $table = 'evaluaciones';

    protected $fillable = [
        'cliente_id',
        'es_odontograma',
        'fecha',
        'limpieza_periodontal',
        'fluor',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'es_odontograma' => 'boolean',
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
