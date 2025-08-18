<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'consultorio_id',
        'telefono',
        'estado',
        'start_at',
        'end_at',
        'created_by',
        'updated_by',
    ];

    /* Relaciones */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function consultorio()
    {
        return $this->belongsTo(Consultorio::class);
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'event_especialidad')
            ->withTimestamps();
    }

    public function servicios()
    {
        return $this->belongsToMany(
            ServicioEspecialidad::class, // Modelo relacionado
            'event_servicio',            // Tabla pivote
            'event_id',                  // FK de este modelo en la pivote
            'servicio_id'                // FK del modelo relacionado en la pivote (¡clave!)
        )->withTimestamps();
    }


    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
