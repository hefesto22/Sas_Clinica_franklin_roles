<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use HasFactory;

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
}
