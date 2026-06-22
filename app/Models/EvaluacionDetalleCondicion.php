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

    /** Tamaños/extensión de una condición (ej: obturación 1/2/3). */
    public const TAMANOS = [
        'pequena' => 'Pequeña',
        'mediana' => 'Mediana',
        'grande'  => 'Grande',
    ];

    protected $fillable = [
        'evaluacion_detalle_id',
        'origen_evaluacion_id',
        'condicion',
        'nota',
        'nota_tratamiento',
        'tamano',
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

    /** Etiqueta legible del tamaño (Pequeña/Mediana/Grande), o null. */
    public function getTamanoLabelAttribute(): ?string
    {
        return $this->tamano ? (self::TAMANOS[$this->tamano] ?? $this->tamano) : null;
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
