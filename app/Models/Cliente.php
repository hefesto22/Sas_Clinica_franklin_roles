<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use HasFactory;
    use \App\Models\Concerns\RegistraAuditoria;
    use \Illuminate\Database\Eloquent\SoftDeletes; // los pacientes se archivan, no se borran

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'dni',
        'telefono',
        'direccion',
        'ocupacion',
        'fecha_nacimiento',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'motivo_consulta',
        'alergias',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    // Relación con usuario creador
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con usuario que actualizó
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function actividades()
    {
        return $this->hasMany(ClienteActividad::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ClienteImagen::class);
    }

    public function notas()
    {
        return $this->hasMany(ClienteNota::class);
    }
    public function evaluaciones()
    {
        // si quieres las más recientes primero:
        return $this->hasMany(Evaluacion::class)->latest('fecha');
        // o simplemente: return $this->hasMany(Evaluacion::class);
    }

    /**
     * Odontograma único del paciente.
     *
     * Decisión de negocio (2026-06-15): un solo odontograma por paciente que
     * acumula los tratamientos en el tiempo. Devuelve la Evaluacion existente
     * o crea una si el paciente aún no tiene. El historial por pieza vive en
     * las filas de EvaluacionDetalleCondicion colgando de esta evaluación.
     */
    public function odontograma(): Evaluacion
    {
        return $this->evaluaciones()->firstOrCreate(
            ['cliente_id' => $this->getKey()],
            ['fecha' => now()->toDateString(), 'user_id' => auth()->id()],
        );
    }
}
