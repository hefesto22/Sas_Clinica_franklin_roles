<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionDetalle extends Model
{
    use HasFactory;
    use \App\Models\Concerns\RegistraAuditoria;

    /**
     * Catálogo de condiciones clínicas por pieza, con su color en el
     * odontograma (paleta estándar de fichas dentales).
     */
    public const CONDICIONES = [
        'caries'              => ['label' => 'Caries',              'color' => '#ef4444'],
        'obturacion'          => ['label' => 'Obturación',          'color' => '#3b82f6'],
        'corona'              => ['label' => 'Corona',              'color' => '#a855f7'],
        'endodoncia'          => ['label' => 'Endodoncia',          'color' => '#0ea5e9'],
        'implante'            => ['label' => 'Implante',            'color' => '#64748b'],
        'sellante'            => ['label' => 'Sellante',            'color' => '#14b8a6'],
        'fractura'            => ['label' => 'Fractura',            'color' => '#f97316'],
        'extraccion_indicada' => ['label' => 'Extracción indicada', 'color' => '#dc2626'],
        'ausente'             => ['label' => 'Ausente',             'color' => null],
        'otro'                => ['label' => 'Otro',                'color' => '#f59e0b'],
    ];

    protected $fillable = [
        'evaluacion_id',
        'pieza',
        'condiciones',
        'diagnostico',
        'hecho', // Nuevo campo para el checkbox
    ];

    protected $casts = [
        'hecho'       => 'boolean',
        'condiciones' => 'array',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class);
    }
}
