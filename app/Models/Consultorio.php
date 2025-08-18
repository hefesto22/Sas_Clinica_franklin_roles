<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Consultorio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'modo_defecto',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_by = $model->created_by ?? Auth::id();
            $model->updated_by = $model->updated_by ?? Auth::id();
        });

        static::updating(function (self $model) {
            $model->updated_by = Auth::id();
        });
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(ConsultorioTurno::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
