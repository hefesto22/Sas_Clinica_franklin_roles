<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteActividad extends Model
{
    use HasFactory;
    protected $table = 'cliente_actividades';
    protected $fillable = [
        'cliente_id',
        'fecha',
        'actividad',
        'pago',
    ];

    protected $casts = [
        'fecha' => 'date',
        'pago'  => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
