<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una condición clínica registrada sobre una pieza dental, con su nota y
 * sus fechas. La misma condición puede repetirse en el tiempo sobre la
 * misma pieza (recurrencia), por eso cada ocurrencia es una fila propia.
 *
 * El catálogo de condiciones válidas y sus colores vive en
 * {@see EvaluacionDetalle::CONDICIONES}.
 */
class EvaluacionDetalleCondicion extends Model
{
    use HasFactory;
    use \App\Models\Concerns\RegistraAuditoria;
    use SoftDeletes;

    protected $table = 'evaluacion_detalle_condiciones';

    protected $fillable = [
        'evaluacion_detalle_id',
        'condicion',
        'nota',
        'nota_tratamiento',
        'tratada',
        'detectada_en',
        'tratada_en',
    ];

    protected $casts = [
        'tratada'      => 'boolean',
        'detectada_en' => 'date',
        'tratada_en'   => 'date',
    ];

    public function detalle()
    {
        return $this->belongsTo(EvaluacionDetalle::class, 'evaluacion_detalle_id');
    }

    /** Etiqueta legible de la condición según el catálogo. */
    public function getEtiquetaAttribute(): string
    {
        return EvaluacionDetalle::CONDICIONES[$this->condicion]['label'] ?? $this->condicion;
    }

    /** Color de la condición según el catálogo (null = sin color, ej. ausente). */
    public function getColorAttribute(): ?string
    {
        return EvaluacionDetalle::CONDICIONES[$this->condicion]['color'] ?? null;
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('tratada', false);
    }

    public function scopeTratadas(Builder $query): Builder
    {
        return $query->where('tratada', true);
    }
}
