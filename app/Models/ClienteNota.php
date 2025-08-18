<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ClienteNota extends Model
{
    use HasFactory;

    protected $table = 'cliente_notas';

    protected $fillable = [
        'cliente_id',
        'contenido',
        'leida',
        'created_by',
    ];

    protected $casts = [
        'leida' => 'boolean',
    ];
    protected static function booted(): void
    {
        static::creating(function (ClienteNota $nota) {
            if (blank($nota->created_by)) {
                $nota->created_by = Auth::id(); // 👈 siempre guarda el usuario actual
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
