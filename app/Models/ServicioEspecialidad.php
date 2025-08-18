<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ServicioEspecialidad extends Model
{
    use HasFactory;

    protected $table = 'servicios';

    protected $fillable = [
        'especialidad_id',
        'nombre',
        'descripcion',
        'precio',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->especialidad_id = $m->especialidad_id ?? request()->route('record');
            $m->estado = $m->estado ?? 'activo';
            $m->created_by = $m->created_by ?? Auth::id(); // 👈 ahora sí
        });

        static::updating(function ($m) {
            $m->updated_by = Auth::id(); // 👈 aquí también
        });
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class);
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
